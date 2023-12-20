<?php

declare(strict_types=1);

namespace McMatters\CsFixer\NodeVisitor;

use Illuminate\Support\Facades\Config;
use PhpParser\Node;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\NodeTraverser;

use function preg_replace;

use const false;
use const null;
use const true;

class StrictTypeDeclarationResolver extends ChangeableContentNodeVisitor
{
    protected bool $isDeclared = false;

    /**
     * @return int|void
     */
    public function enterNode(Node $node)
    {
        if (
            !$this->isDeclared &&
            $node instanceof DeclareDeclare &&
            (string) $node->key === 'strict_types'
        ) {
            $this->isDeclared = true;

            return NodeTraverser::STOP_TRAVERSAL;
        }
    }

    public function afterTraverse(array $nodes): void
    {
        if (!$this->isDeclared) {
            $this->replaceContent();
        }
    }

    protected function replaceContent(): void
    {
        static $config;

        if (null === $config) {
            $config = Config::get('cs-fixer.declare_strict_types.replacing');
        }

        $this->content = preg_replace(
            $config['pattern'] ?? '',
            $config['replacement'] ?? '',
            $this->content,
            1,
        );

        $this->wasContentChanged = true;
    }
}
