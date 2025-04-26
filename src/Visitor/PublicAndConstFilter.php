<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

class PublicAndConstFilter extends ModuleVisitor
{
    /**
     * @param mixed $node
     *
     * @return null|int|Node|Node[]
     */
    public function leaveNode($node): null|array|int|Node
    {
        if ($this->needSkip($node)) {
            return null;
        }

        if ($node instanceof Class_ || $node instanceof Node\Stmt\Trait_) {
            if (!isset($node->stmts)) {
                return null;
            }
            $node->stmts = array_filter(
                $node->stmts,
                static fn($stmt) => ($stmt instanceof Node\Stmt\ClassMethod && $stmt->isPublic())
                    || ($stmt instanceof Node\Stmt\ClassConst),
            );
        }

        return null;
    }
}
