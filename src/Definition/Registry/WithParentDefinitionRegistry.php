<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

abstract class WithParentDefinitionRegistry implements DefinitionRegistry
{
    private ?DefinitionRegistry $parentDefinitionRegistry;

    public function setParentDefinitionRegistry(DefinitionRegistry $parentDefinitionRegistry = null): void
    {
        $this->parentDefinitionRegistry = $parentDefinitionRegistry;
    }

    public function getParentDefinitionRegistry(): DefinitionRegistry
    {
        return $this->parentDefinitionRegistry ?? $this;
    }
}
