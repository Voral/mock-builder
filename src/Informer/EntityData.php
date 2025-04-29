<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Informer;

use Vasoft\MockBuilder\Graph;

class EntityData
{
    private array $methodsReturnTypes = [];

    public function __construct(
        public readonly EntityType $type,
        public readonly string $className,
        public readonly string $filePath,
        public array $interfaces = [],
        public array $parents = [],
        public array $children = [],
    ) {}

    public function setMethodReturnType(string $methodName, mixed $type): void
    {
        $this->methodsReturnTypes[$methodName] = $type;
    }

    public function hasMethodReturnType(string $methodName): bool
    {
        return isset($this->methodsReturnTypes[$methodName]);
    }

    public function getMethodReturnType(string $methodName): mixed
    {
        return $this->methodsReturnTypes[$methodName];
    }

    /**
     * Recursively searches for the return type of a method in parent interfaces and classes.
     *
     * @param string $methodName the name of the method to search for
     * @param Graph  $graph      the dependency graph used for lookup
     *
     * @return mixed the return type of the method, or null if not found
     */
    public function getMethodReturnTypeRecursively(string $methodName, Graph $graph): mixed
    {
        if ($this->hasMethodReturnType($methodName)) {
            return $this->getMethodReturnType($methodName);
        }
        foreach ($this->interfaces as $parentClassName) {
            $parent = $graph->getEntityData($parentClassName);
            if (null !== $parent) {
                $result = $parent->getMethodReturnTypeRecursively($methodName, $graph);
                if (null !== $result) {
                    return $result;
                }
            }
        }
        foreach ($this->parents as $parentClassName) {
            $parent = $graph->getEntityData($parentClassName);
            if (null !== $parent) {
                $result = $parent->getMethodReturnTypeRecursively($methodName, $graph);
                if (null !== $result) {
                    return $result;
                }
            }
        }

        return null;
    }
}
