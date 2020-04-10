<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Converter\ConverterInterface;
use Goat\Mapper\Definition\Column;

/**
 * Entity value column.
 */
class Value extends Property
{
    private Column $column;

    public function __construct(string $name, ?string $columnName = null, ?string $type = null)
    {
        parent::__construct($name);

        $this->column = new Column($columnName ?? $name, $type ?? ConverterInterface::TYPE_UNKNOWN);
    }

    public function getColumn(): Column
    {
        return $this->column;
    }

    public function getType(): string
    {
        return $this->column->getType();
    }

    public function getColumnName(): string
    {
        return $this->column->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): iterable
    {
        return [];
    }
}
