<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Informer;

enum EntityType: string
{
    case IS_CLASS = 'class';
    case IS_INTERFACE = 'interface';
    case IS_TRAIT = 'trait';
}
