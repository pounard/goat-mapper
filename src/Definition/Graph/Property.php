<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

/**
 * Represents a single entity property.
 */
interface Property extends Node
{
    /**
     * Get property name.
     */
    public function getName(): string;

    /**
     * Does this property allows null values.
     */
    public function allowsNull(): bool;

    /**
     * Get owner entity.
     */
    public function getOwner(): Entity;
}
