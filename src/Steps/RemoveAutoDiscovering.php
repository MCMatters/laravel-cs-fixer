<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Steps;

use Illuminate\Contracts\Foundation\Application;
use McMatters\ComposerHelper\ComposerHelper;
use McMatters\CsFixer\Contracts\Step;

use function array_merge_recursive;
use function array_values;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function json_encode;
use function ksort;
use function mb_strpos;
use function mb_substr;
use function preg_replace;
use function sort;
use function stripos;
use function str_repeat;
use function trim;
use function unlink;

use const false;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

/**
 * Class RemoveAutoDiscovering
 *
 * @package McMatters\CsFixer\Steps
 */
class RemoveAutoDiscovering implements Step
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var \McMatters\ComposerHelper\ComposerHelper
     */
    protected $composer;

    /**
     * RemoveAutoDiscovering constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->composer = new ComposerHelper($app->basePath());
    }

    /**
     * @return void
     *
     * @throws \McMatters\ComposerHelper\Exceptions\EmptyFileException
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(): void
    {
        $config = $this->composer->getComposerConfig();

        if ($config['extra']['laravel']['dont-discover'] ?? '' === '*') {
            return;
        }

        $this->writeComposerConfig($config);
        $this->removeCachedFiles();

        $packages = $this->filterIncludedPackages($this->getDiscoverPackages());

        $this->writeExtraToAppConfig($packages);
    }

    /**
     * @param array $config
     *
     * @return void
     */
    protected function writeComposerConfig(array $config): void
    {
        $config['extra']['laravel']['dont-discover'] = ['*'];

        foreach ($config['scripts'] ?? [] as $hook => $scripts) {
            foreach ($scripts as $key => $script) {
                if (stripos($script, 'php artisan package:discover') !== false) {
                    unset($config['scripts'][$hook][$key]);
                }
            }

            $config['scripts'][$hook] = array_values($config['scripts'][$hook]);
        }

        file_put_contents(
            $this->composer->getComposerConfigPath(),
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @return void
     */
    protected function removeCachedFiles(): void
    {
        @unlink($this->app->getCachedPackagesPath());
        @unlink($this->app->getCachedServicesPath());
    }

    /**
     * @param array $packages
     *
     * @return void
     */
    protected function writeExtraToAppConfig(array $packages): void
    {
        $indent = 8;
        $glue = "\n".str_repeat(' ', $indent);

        $configAppPath = $this->app->configPath('app.php');

        $content = file_get_contents($configAppPath);
        $content = $this->appendProviders($packages['providers'] ?? [], $content, $glue);
        $content = $this->appendAliases($packages['aliases'] ?? [], $content, $glue);

        file_put_contents($configAppPath, $content);
    }

    /**
     * @param array $providers
     * @param string $content
     * @param string $glue
     *
     * @return string
     */
    protected function appendProviders(
        array $providers,
        string $content,
        string $glue
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

    /**
     * @param array $aliases
     * @param string $content
     * @param string $glue
     *
     * @return string
     */
    protected function appendAliases(
        array $aliases,
        string $content,
        string $glue
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
     * @return array
     *
     * @throws \McMatters\ComposerHelper\Exceptions\EmptyFileException
     * @throws \McMatters\ComposerHelper\Exceptions\FileNotFoundException
     */
    protected function getDiscoverPackages(): array
    {
        $discoverPackages = ['providers' => [], 'aliases' => []];
        $packages = [];

        foreach ($this->composer->getAllExtra() as $extra) {
            if (empty($extra['laravel'])) {
                continue;
            }

            $packages[] = $extra['laravel'];
        }

        return array_merge_recursive($discoverPackages, ...$packages);
    }

    /**
     * @param array $packages
     *
     * @return array
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function filterIncludedPackages(array $packages): array
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $this->app->make('config');

        $providers = $config->get('app.providers', []);
        $aliases = $config->get('app.aliases', []);

        foreach (['providers', 'aliases'] as $type) {
            foreach ($packages[$type] ?? [] as $key => $item) {
                if (in_array($item, $$type, true)) {
                    unset($packages[$type][$key]);
                }
            }
        }

        return $packages;
    }
}
