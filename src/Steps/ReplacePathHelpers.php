<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Steps;

use Illuminate\Support\Facades\Config;
use McMatters\CsFixer\Contracts\Step;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function count;
use function file_put_contents;
use function ltrim;
use function preg_replace_callback;
use function trim;

use const false;
use const true;

class ReplacePathHelpers implements Step
{
    protected array $config;

    public function __construct()
    {
        $this->config = Config::get('cs-fixer.replace_path_helpers');
    }

    public function handle(): void
    {
        $this->replacePathHelpersInConfig();
        $this->replacePathHelpersInProviders();
        $this->replacePathHelpersInConsole();
    }

    protected function replacePathHelpersInConfig(): void
    {
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles($this->config['config_path']) as $file) {
            $this->replacePathHelpers($file, '$app');
        }
    }

    protected function replacePathHelpersInProviders(): void
    {
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles($this->config['provider_path']) as $file) {
            $this->replacePathHelpers($file, '$this->app');
        }
    }

    protected function replacePathHelpersInConsole(): void
    {
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles($this->config['console_path']) as $file) {
            if ($file->getFilename() === 'Kernel.php') {
                $this->replacePathHelpers($file, '$this->app');
            } else {
                $this->replacePathHelpers($file, '$this->laravel');
            }
        }
    }

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

                        return "\"{{$prefix}->{$data['replace']}()}/".trim(ltrim($match[1], '/'), "'").'"';
                    }

                    if ($matchesCount === 5) {
                        if ($data['args']) {
                            return "{$prefix}->{$data['replace']}('".ltrim($match[4], '/')."')";
                        }

                        return "\"{{$prefix}->{$data['replace']}()}/".ltrim($match[4], '/').'"';
                    }

                    return $match[0];
                }, $content);
        }

        file_put_contents($file->getPathname(), $content);
    }
}
