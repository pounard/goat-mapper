<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\Column;

/**
 * Entity value column.
 */
interface Value extends Property
{
    /**
     * Get column.
     */
    public function getColumn(): Column;

    /**
     * Get SQL column type.
     */
    public function getColumnType(): string;

    /**
     * Get SQL column name.
     */
    public function getColumnName(): string;
}
