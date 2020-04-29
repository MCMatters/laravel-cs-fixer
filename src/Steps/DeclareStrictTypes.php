<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Steps;

use Illuminate\Support\Facades\Config;
use McMatters\CsFixer\Contracts\Step;
use McMatters\CsFixer\NodeVisitor\StrictTypeDeclarationResolver;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

use function file_put_contents;

use const true;

/**
 * Class DeclareStrictTypes
 *
 * @package McMatters\CsFixer\Steps
 */
class DeclareStrictTypes implements Step
{
    /**
     * @return void
     */
    public function handle(): void
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles() as $file) {
            $content = $file->getContents();

            $ast = $parser->parse($content);
            $traverser = new NodeTraverser();

            $traverser->addVisitor($visitor = new StrictTypeDeclarationResolver($content));
            $traverser->traverse($ast);

            if ($visitor->wasContentChanged()) {
                file_put_contents($file->getPathname(), $visitor->getContent());
            }
        }
    }

    /**
     * @return \Symfony\Component\Finder\Finder
     */
    protected function getFiles(): Finder
    {
        return Finder::create()
            ->ignoreDotFiles(true)
            ->ignoreUnreadableDirs()
            ->ignoreVCS(true)
            ->name('*.php')
            ->files()
            ->in(Config::get('cs-fixer.declare_strict_types.paths'));
    }
}
