<?php

declare(strict_types=1);

use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\SectionRules;

return (new Config())
    ->setHideDoubles(true)
    ->setSection('breaking', 'BREAKING CHANGES', 0)
    ->addSectionRule('breaking', new SectionRules\BreakingRule())
    ->setEnabledComposerVersioning(false);
