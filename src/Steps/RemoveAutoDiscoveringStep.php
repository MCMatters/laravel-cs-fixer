<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Steps;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use McMatters\ComposerHelper\ComposerHelper;

use function array_merge_recursive;
use function array_values;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function json_encode;
use function ksort;
use function mb_strpos;
use function mb_substr;
use function method_exists;
use function preg_replace;
use function sort;
use function stripos;
use function str_repeat;
use function trim;
use function unlink;

use const false;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class RemoveAutoDiscoveringStep extends AbstractStep
{
    protected Application $app;

    protected ComposerHelper $composer;

    /**
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->app = App::getInstance();
        $this->composer = new ComposerHelper($this->app->basePath());
    }

    /**
     * @throws \McMatters\ComposerHelper\Exceptions\EmptyFileException
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     * @throws \JsonException
     */
    public function handle(array $config = []): void
    {
        parent::handle($config);

        $content = $this->composer->getComposerJsonContent();

        if ($content['extra']['laravel']['dont-discover'] ?? '' === '*') {
            return;
        }

        $this->updateComposerJsonContent($content);
        $this->removeCachedFiles();

        $packages = $this->filterPackages($this->getDiscoverPackages());

        $this->updateAppConfig($packages);
    }

    protected function updateComposerJsonContent(array $content): void
    {
        $content['extra']['laravel']['dont-discover'] = ['*'];

        foreach ($content['scripts'] ?? [] as $hook => $scripts) {
            foreach ($scripts as $key => $script) {
                if (stripos($script, 'php artisan package:discover') !== false) {
                    unset($content['scripts'][$hook][$key]);
                }
            }

            $content['scripts'][$hook] = array_values($content['scripts'][$hook]);
        }

        file_put_contents(
            $this->composer->getComposerJsonPath(),
            json_encode(
                $content,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            )
        );
    }

    protected function removeCachedFiles(): void
    {
        if (method_exists($this->app, 'getCachedPackagesPath')) {
            @unlink($this->app->getCachedPackagesPath());
        }

        if (method_exists($this->app, 'getCachedServicesPath')) {
            @unlink($this->app->getCachedServicesPath());
        }
    }

    protected function updateAppConfig(array $packages): void
    {
        $indent = 8;
        $glue = "\n".str_repeat(' ', $indent);

        $configAppPath = $this->app->configPath('app.php');

        $content = file_get_contents($configAppPath);
        $content = $this->appendProviders($packages['providers'] ?? [], $content, $glue);
        $content = $this->appendAliases($packages['aliases'] ?? [], $content, $glue);

        file_put_contents($configAppPath, $content);
    }

    protected function appendProviders(
        array $providers,
        string $content,
        string $glue,
    ): string {
        if (!$providers) {
            return $content;
        }

        $mark = mb_strpos($content, 'Package Service Providers...');

        if (false === $mark) {
            if (false === $mark = mb_strpos($content, "'providers'")) {
                return $content;
            }

            if (false === $mark = mb_strpos($content, ']', $mark)) {
                return $content;
            }

            $pattern = '/]/';
            $replacePrefix = "\n";
            $replaceSuffix = "\n    ]";
        } else {
            if (false === $mark = mb_strpos($content, '*/', $mark)) {
                return $content;
            }

            $mark += 2; // add offset of "*/"
            $pattern = "/\n/";
            $replacePrefix = '';
            $replaceSuffix = "\n";
        }

        $rows = [];

        sort($providers);

        foreach ($providers as $provider) {
            $rows[] = "{$provider}::class,";
        }

        $rows = implode($glue, $rows);

        $piece = mb_substr($content, 0, $mark);

        $injected = preg_replace(
            $pattern,
            "{$replacePrefix}{$glue}{$rows}{$replaceSuffix}",
            mb_substr($content, $mark),
            1
        );

        return trim($piece).$injected;
    }

    protected function appendAliases(
        array $aliases,
        string $content,
        string $glue,
    ): string {
        if (!$aliases) {
            return $content;
        }

        if (false === $mark = mb_strpos($content, "'aliases'")) {
            return $content;
        }

        if (false === $mark = mb_strpos($content, ']', $mark)) {
            return $content;
        }

        $piece = mb_substr($content, 0, $mark);

        $rows = [];

        ksort($aliases);

        foreach ($aliases as $alias => $map) {
            $rows[] = "'{$alias}' => {$map}::class,";
        }

        $rows = implode($glue, $rows);

        $injected = preg_replace(
            '/]/',
            "{$glue}{$rows}\n    ]",
            mb_substr($content, $mark),
            1
        );

        return trim($piece)."\n{$injected}";
    }

    /**
     * @throws \McMatters\ComposerHelper\Exceptions\EmptyFileException
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     * @throws \JsonException
     */
    protected function getDiscoverPackages(): array
    {
        $discoverPackages = ['providers' => [], 'aliases' => []];
        $packages = [];

        foreach ($this->composer->getExtras() as $extra) {
            if (empty($extra['laravel'])) {
                continue;
            }

            $packages[] = $extra['laravel'];
        }

        return array_merge_recursive($discoverPackages, ...$packages);
    }

    protected function filterPackages(array $packages): array
    {
        foreach (['providers', 'aliases'] as $type) {
            $existing = Config::get("app.{$type}", []);
            $exclude = $this->config['exclude'][$type] ?? [];

            foreach ($packages[$type] ?? [] as $key => $item) {
                if (
                    in_array($item, $existing[$type], true) ||
                    in_array($item, $exclude[$type], true)
                ) {
                    unset($packages[$type][$key]);
                }
            }
        }

        return $packages;
    }
}
