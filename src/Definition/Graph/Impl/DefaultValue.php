<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Converter\ConverterInterface;
use Goat\Mapper\Definition\Column;
use Goat\Mapper\Definition\Graph\Value;

final class DefaultValue extends AbstractProperty implements Value
{
    private Column $column;

    public function __construct(string $name, ?string $columnName = null, ?string $type = null)
    {
        parent::__construct($name);

        $this->column = new Column($columnName ?? $name, $type ?? ConverterInterface::TYPE_UNKNOWN);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumn(): Column
    {
        return $this->column;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(): string
    {
        return $this->column->getType();
    }

    /**
     * {@inheritdoc}
     */
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
