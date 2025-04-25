<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

abstract class ModuleVisitor extends NodeVisitorAbstract
{
    public function __construct(
        protected readonly bool $skipThrowable = false,
    ) {}

    protected function needSkip($node): bool
    {
        return $this->skipThrowable && $node instanceof Class_ && $this->isThrowable($node);
    }

    private function isThrowable(Class_ $class): bool
    {
        foreach ($class->implements as $interface) {
            if ('Throwable' === $interface->getLast()) {
                return true;
            }
        }

        if ($class->extends instanceof Name) {
            $parentClassName = $class->extends->toString();

            return $this->isParentThrowable($parentClassName);
        }

        return false;
    }

    private function isParentThrowable(string $className): bool
    {
        try {
            $reflection = new \ReflectionClass($className);

            return $reflection->implementsInterface(\Throwable::class);
        } catch (\ReflectionException $e) {
            return false;
        }
    }
}
