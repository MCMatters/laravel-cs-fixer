<?php

declare(strict_types = 1);

namespace McMatters\CsFixer\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use const null, true;
use function array_splice, explode, implode, strlen, substr;

/**
 * Class ClassDocBlockResolver
 *
 * @package McMatters\CsFixer\NodeVisitor
 */
class ClassDocBlockResolver extends ChangeableContentNodeVisitor
{
    /**
     * @param \PhpParser\Node $node
     *
     * @return int|void
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            if (null !== $node->getDocComment()) {
                return NodeTraverser::STOP_TRAVERSAL;
            }

            $class = (string) $node->name;
            $replacement = "/**\n * Class {$class}\n";

            if (null !== $node->namespacedName) {
                $package = substr(
                    (string) $node->namespacedName,
                    0,
                    -strlen("\\{$class}")
                );

                if ($package) {
                    $replacement .= " *\n * @package {$package}\n";
                }
            }

            $this->replaceContent($node, $replacement);
        }
    }

    /**
     * @param \PhpParser\Node $node
     * @param string $replacement
     *
     * @return void
     */
    protected function replaceContent(Node $node, string $replacement): void
    {
        $content = explode("\n", $this->content);

        array_splice(
            $content,
            $node->getStartLine() - 1,
            0,
            "{$replacement} */"
        );

        $this->content = implode("\n", $content);

        $this->wasContentChanged = true;
    }
}
