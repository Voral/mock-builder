<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use Vasoft\MockBuilder\Config;
use Vasoft\MockBuilder\Graph;

abstract class ModuleVisitor extends NodeVisitorAbstract
{
    protected ?Graph $dependenceGraph = null;
    protected ?Config $config = null;

    public function __construct(
        protected readonly bool $skipThrowable = false,
    ) {}

    public function setDependenceGraph(Graph $dependenciesGraph): static
    {
        $this->dependenceGraph = $dependenciesGraph;

        return $this;
    }

    protected function needSkip($node): bool
    {
        return $this->skipThrowable && $node instanceof Class_ && $this->isThrowable($node);
    }

    private function isThrowable(Class_ $class): bool
    {
        if (null === $this->dependenceGraph || (empty($class->name) && empty($class->namespacedName))) {
            return false;
        }
        $className = $class->namespacedName?->toString() ?? $class->name->toString();

        return $this->dependenceGraph->isInstanceOf($className, ['Throwable', 'Exception']);
    }

    public function beforeProcess(): void {}

    public function setConfig(Config $config): static
    {
        $this->config = $config;

        return $this;
    }
}
