<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use Vasoft\MockBuilder\Graph;

abstract class ModuleVisitor extends NodeVisitorAbstract
{
    protected ?Graph $dependenceGraph = null;

    public function __construct(
        protected readonly bool $skipThrowable = false,
    ) {}

    public function setDependenceGraph(Graph $dependenciesGraph): void
    {
        $this->dependenceGraph = $dependenciesGraph;
    }

    protected function needSkip($node): bool
    {
        return $this->skipThrowable && $node instanceof Class_ && $this->isThrowable($node);
    }

    private function isThrowable(Class_ $class): bool
    {
        if (null === $this->dependenceGraph) {
            return false;
        }
        $className = $class->namespacedName?->toString() ?? $class->name->toString();

        return $this->dependenceGraph->isInstanceOf($className, ['Throwable', 'Exception']);
    }
}
