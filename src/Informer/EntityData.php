<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Informer;

class EntityData
{
    public function __construct(
        public readonly EntityType $type,
        public readonly string $className,
        public readonly string $filePath,
        public array $interfaces = [],
        public array $parents = [],
        public array $children = [],
    ) {}
}
