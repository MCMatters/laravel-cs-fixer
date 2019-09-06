<?php

declare(strict_types = 1);

namespace McMatters\CsFixer\NodeVisitor;

use PhpParser\NodeVisitorAbstract;

use const false;

/**
 * Class ChangeableContentNodeVisitor
 *
 * @package McMatters\CsFixer\NodeVisitor
 */
abstract class ChangeableContentNodeVisitor extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var bool
     */
    protected $wasContentChanged = false;

    /**
     * ClassDocBlockResolver constructor.
     *
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return bool
     */
    public function wasContentChanged(): bool
    {
        return $this->wasContentChanged;
    }
}
