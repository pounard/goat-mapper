<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Table;

/**
 * Represent a many to many relation table.
 */
class Mapping
{
    private Table $table;
    private Key $sourceKey;
    private Key $targetKey;

    public function __construct(Table $table, Key $sourceKey, Key $targetKey)
    {
        $this->table = $table;
        $this->sourceKey = $sourceKey;
        $this->targetKey = $targetKey;
    }

    /**
     * Get mapping table.
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * Get target relation key.
     */
    public function getTargetKey(): Key
    {
        return $this->targetKey;
    }

    /**
     * Get source relation key.
     */
    public function getSourceKey(): Key
    {
        return $this->sourceKey;
    }
}
