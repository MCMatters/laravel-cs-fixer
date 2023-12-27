<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Steps;

use Symfony\Component\Finder\Finder;

use function file_put_contents;
use function preg_replace;
use function preg_replace_callback;

use const true;

class NormalizePhpDoc extends AbstractStep
{
    public function handle(array $config = []): void
    {
        parent::handle($config);

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles() as $file) {
            $content = preg_replace_callback('~/\*.*?\*/~s', static function ($match) {
                $match = $match[0];

                $match = preg_replace_callback('/\* @param\s*.*/', static function ($match) {
                    return preg_replace('/\s{2,}/', ' ', $match[0]);
                }, $match);

                return preg_replace_callback('/(\*\n)?((\s*)\* @return)/', static function ($match) {
                    if ($match[1] !== '') {
                        return $match[0];
                    }

                    return "{$match[3]}*{$match[0]}";
                }, $match);
            }, $file->getContents());

            file_put_contents($file->getPathname(), $content);
        }
    }

    protected function getFiles(): Finder
    {
        return Finder::create()
            ->ignoreDotFiles(true)
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->name('*.php')
            ->files()
            ->in($this->config['paths']);
    }
}
