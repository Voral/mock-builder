<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder;

final class Config
{
    /**
     * @param string   $targetPath      target path where the transformed files will be saved
     * @param string[] $basePath        base path for resolving relative paths
     * @param string[] $classNameFilter list of class name filters to include specific classes during processing
     * @param bool     $displayProgress whether to display the progress during processing
     */
    public function __construct(
        public readonly string $targetPath = '',
        public readonly array $basePath = [],
        public readonly array $classNameFilter = [],
        public readonly bool $displayProgress = false,
    ) {}
}
