<?php

declare(strict_types=1);

namespace McMatters\CsFixer\Steps;

use McMatters\CsFixer\NodeVisitor\StrictTypeDeclarationResolver;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

use function file_put_contents;

use const true;

class DeclareStrictTypes extends AbstractStep
{
    public function handle(array $config = []): void
    {
        parent::handle($config);

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->getFiles() as $file) {
            $content = $file->getContents();

            $ast = $parser->parse($content);
            $traverser = new NodeTraverser();

            $visitor = new StrictTypeDeclarationResolver($content, $this->config);

            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            if ($visitor->wasContentChanged()) {
                file_put_contents($file->getPathname(), $visitor->getContent());
            }
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
