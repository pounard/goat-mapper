<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

class EntityDefinition
{
    /** @var string */
    private $className;

    /** @var array<string,string> */
    private $columnMap = [];

    /** @param array<string,string> $columnMap */
    public function __construct(string $className, array $columnMap = [])
    {
        $this->className = $className;
        $this->columnMap = $columnMap;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Get property name list
     *
     * @return string[]
     */
    public function getPropertyNames(): array
    {
        return \array_keys($this->columnMap);
    }

    public function getColumn(string $propertyName): ?string
    {
        return $this->columnMap[$propertyName] ?? null;
    }

    /**
     * Get property column map
     *
     * @return array<string,string>
     *   Keys are property names, values are columns.
     */
    public function getColumnMap(): array
    {
        return $this->columnMap;
    }
}
