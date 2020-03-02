<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

class Key
{
    /** @var Column[] */
    private $columns;

    /** @var null|string[] */
    private $columnNames = null;

    /**
     * @param Column[] $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = \array_values($columns);
    }

    public function isEmpty(): bool
    {
        return empty($this->columns);
    }

    /** @return Column[] */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @return string[] */
    public function getColumnNames(): array
    {
        return $this->columnNames ?? $this->columnNames = $this->createColumnNameArray();
    }

    public function count(): int
    {
        return \count($this->columns);
    }

    public function isCompatible(Key $other): bool
    {
        // This is a very naive algorithm. 
        $expectedSize = \count($this->columns);

        if (\count($other->columns) !== $expectedSize) {
            return false;
        }

        for ($i = 0; $i < $expectedSize; $i++) {
            if ($other->columns[$i]->getType() !== $this->columns[$i]->getType()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Allow caching of this name array for performance purpose: this will
     * probably be called much more than once.
     */
    private function createColumnNameArray(): array
    {
        $ret = [];
        foreach ($this->columns as $column) {
            $ret[] = $column->getName();
        }
        return $ret;
    }
}
