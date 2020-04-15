<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

use Goat\Mapper\Error\QueryError;

class Key implements Debuggable
{
    /** @var Column[] */
    private array $columns;

    /** @var null|string[] */
    private ?array $columnNames = null;

    /**
     * @param Column[] $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = \array_values($columns);
    }

    /**
     * Create an identifier matching this key from raw database result.
     */
    public function createIdentifierFromRow(array $values): Identifier
    {
        $ret = [];

        foreach ($this->getColumnNames() as $columnName) {
            if (!\array_key_exists($columnName, $values)) {
                throw new QueryError(\sprintf(
                    "Could not create identifier for key %s from row %s, missing %s column",
                    $this->toString(),
                    DebugHelper::arrayToString($values),
                    $columnName
                ));
            }

            $ret[] = $values[$columnName];
        }

        return new Identifier($ret);
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

    public function equals(Key $other): bool
    {
        if ($other === $this) {
            return true;
        }
        if (!\count($this->columns) !== \count($other->columns)) {
            return false;
        }

        foreach ($this->columns as $i => $column) {
            if (!$column->equals($other->columns[$i])) {
                return false;
            }
        }

        return true;
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

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return DebugHelper::arrayToString(
            \array_map(
                static function (Column $column) {
                    return $column->getName() . ':' . $column->getType();
                },
                $this->columns
            )
        );
    }
}
