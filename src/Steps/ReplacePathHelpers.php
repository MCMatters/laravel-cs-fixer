<?php

declare(strict_types = 1);

namespace McMatters\CsFixer\Steps;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use McMatters\CsFixer\Contracts\Step;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use const false, true;
use function count, file_put_contents, ltrim, preg_replace_callback, trim;

/**
 * Class ReplacePathHelpers
 *
 * @package McMatters\CsFixer\Steps
 */
class ReplacePathHelpers implements Step
{
    /**
     * @var array
     */
    protected $config;

    /**
     * ReplaceHelpers constructor.
     */
    public function __construct()
    {
        $this->config = Config::get('cs-fixer.replace_path_helpers');
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->replacePathHelpersInConfig();
        $this->replacePathHelpersInProviders();
        $this->replacePathHelpersInConsole();
    }

    /**
     * @return void
     */
    protected function replacePathHelpersInConfig(): void
    {
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles(Arr::get($this->config, 'config_path')) as $file) {
            $this->replacePathHelpers($file, '$app');
        }
    }

    /**
     * @return void
     */
    protected function replacePathHelpersInProviders(): void
    {
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles(Arr::get($this->config, 'provider_path')) as $file) {
            $this->replacePathHelpers($file, '$this->app');
        }
    }

    /**
     * @return void
     */
    protected function replacePathHelpersInConsole(): void
    {
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles(Arr::get($this->config, 'console_path')) as $file) {
            if ($file->getFilename() === 'Kernel.php') {
                $this->replacePathHelpers($file, '$this->app');
            } else {
                $this->replacePathHelpers($file, '$this->laravel');
            }
        }
    }

    /**
     * @param string $in
     *
     * @return \Symfony\Component\Finder\Finder
     */
    protected function getFiles(string $in): Finder
    {
        return Finder::create()
            ->ignoreDotFiles(true)
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->name('*.php')
            ->files()
            ->in($in);
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @param string $prefix
     *
     * @return void
     */
    protected function replacePathHelpers(SplFileInfo $file, string $prefix): void
    {
        $paths = [
            'storage_path' => ['replace' => 'storagePath', 'args' => false],
            'database_path' => ['replace' => 'databasePath', 'args' => true],
            'resource_path' => ['replace' => 'resourcePath', 'args' => true],
            'base_path' => ['replace' => 'basePath', 'args' => false],
        ];

        $content = $file->getContents();

        foreach ($paths as $pattern => $data) {
            $content = preg_replace_callback(
                "/{$pattern}\(('.*')?\)(\s*\.\s*(['\"](.+)['\"]))?/",
                static function ($match) use ($prefix, $data) {
                    $matchesCount = count($match);

                    if ($matchesCount === 2) {
                        if ($data['args']) {
                            return "{$prefix}->{$data['replace']}(".ltrim($match[1], '/').')';
                        }

                        return "{$prefix}->{$data['replace']}().'/".trim(ltrim($match[1], '/'), "'")."'";
                    }

                    if ($matchesCount === 5) {
                        if ($data['args']) {
                            return "{$prefix}->{$data['replace']}('".ltrim($match[4], '/').'\')';
                        }

                        return "{$prefix}->{$data['replace']}().'/".ltrim($match[4], '/')."'";
                    }

                    return $match[0];
                }, $content);
        }

        file_put_contents($file->getPathname(), $content);
    }
}
