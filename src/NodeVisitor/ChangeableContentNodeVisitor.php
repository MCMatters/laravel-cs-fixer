<?php

declare(strict_types=1);

namespace McMatters\CsFixer\NodeVisitor;

use PhpParser\NodeVisitorAbstract;

use const false;

abstract class ChangeableContentNodeVisitor extends NodeVisitorAbstract
{
    protected bool $wasContentChanged = false;

    public function __construct(
        protected string $content,
        protected array $config = [],
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function wasContentChanged(): bool
    {
        return $this->wasContentChanged;
    }
}
