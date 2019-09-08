<?php

declare(strict_types = 1);

namespace McMatters\CsFixer\Steps;

use Illuminate\Contracts\Foundation\Application;
use McMatters\ComposerHelper\ComposerHelper;
use McMatters\CsFixer\Contracts\Step;

use function array_merge_recursive, file_get_contents, file_put_contents,
    implode, json_encode, ksort, mb_strpos, mb_substr, preg_replace, sort,
    str_repeat, trim;

use const JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES;

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
     */
    public function handle(): void
    {
        $config = $this->composer->getComposerConfig();

        if ($config['extra']['laravel']['dont-discover'] ?? '' === '*') {
            return;
        }

        $this->writeComposerConfig($config);
        $this->writeExtraToAppConfig($this->getDiscoverPackages());
    }

    /**
     * @param array $config
     *
     * @return void
     */
    protected function writeComposerConfig(array $config): void
    {
        $config['extra']['laravel']['dont-discover'] = ['*'];

        file_put_contents(
            $this->composer->getComposerConfigPath(),
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
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

        $rows = [];

        sort($providers);

        foreach ($providers as $provider) {
            $rows[] = "{$provider}::class,";
        }

        $rows = implode($glue, $rows);

        $mark = mb_strpos($content, 'Package Service Providers...');
        $piece = mb_substr($content, 0, $mark);

        $injected = preg_replace(
            '/ \*\//',
            " */{$glue}{$rows}",
            mb_substr($content, $mark),
            1
        );

        return "{$piece}{$injected}";
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

        $rows = [];

        ksort($aliases);

        foreach ($aliases as $alias => $map) {
            $rows[] = "'{$alias}' => {$map}::class,";
        }

        $rows = implode($glue, $rows);

        $mark = mb_strpos($content, "'aliases'");
        $mark = mb_strpos($content, ']', $mark);
        $piece = mb_substr($content, 0, $mark);

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

        foreach ($this->composer->getAllExtra() as $package => $extra) {
            if (empty($extra['laravel'])) {
                continue;
            }

            $packages[] = $extra['laravel'];
        }

        return array_merge_recursive($discoverPackages, ...$packages);
    }
}
