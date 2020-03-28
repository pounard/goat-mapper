<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

use Goat\Mapper\Error\RelationDoesNotExistError;
use Goat\Mapper\Error\PropertyError;

/**
 * @todo
 *   - repository with different tables to JOIN
 */
class RepositoryDefinition
{
    private string $className;
    private array $columnMap = [];
    private PrimaryKey $primaryKey;
    private Table $table;

    /** @var Relation[] */
    private array $relations = [];

    /**
     * @var array>string,string[]>
     *   Keys are class names, values are array of relation identifiers.
     */
    private $relationClassNameMap = [];

    /**
     * @param Relation[] $relations
     * @param array<string,string> $columnMap
     */
    public function __construct(string $className, array $columnMap = [], Table $table, PrimaryKey $primaryKey, array $relations = [])
    {
        $this->className = $className;
        $this->columnMap = $columnMap;
        $this->primaryKey = $primaryKey;
        $this->table = $table;

        foreach ($relations as $relation) {
            \assert($relation instanceof Relation);

            $propertyName = $relation->getPropertyName();

            if (isset($this->relations[$propertyName])) {
                throw new PropertyError(\sprintf("A relation on property '%s' already exists",  $propertyName));
            }

            $this->relations[$propertyName] = $relation;
            $this->relationClassNameMap[$relation->getClassName()][] = $propertyName;
        }
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

    /**
     * Find appropriate column name for given property name.
     *
     * If nothing found, it returns null, in opposition to relation, it is a
     * non-blocking error not being able to find a property, SQL query builder
     * will pass on the user-provided arbitrary column name that might exist in
     * schema while not being an entity-defined property.
     */
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

    public function getTable(): Table
    {
        return $this->table;
    }

    public function hasPrimaryKey(): bool
    {
        return !$this->primaryKey->isEmpty();
    }

    public function getPrimaryKey(): PrimaryKey
    {
        return $this->primaryKey;
    }

    /** @return Relation[] */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Find relation using an arbitrary identifier.
     *
     * @param string $propertyOrClassName
     *   Can be either of:
     *     - target class name, if more than one relation with the same target
     *       exists, it will raised an exception,
     *     - object property name, if property does not exists or has no
     *       relation, it will raise an exception; please note that if property
     *       is a component of a greater key, it will also fail.
     *
     * @throws RelationDoesNotExistError
     */
    public function getRelation(string $propertyOrClassName): Relation
    {
        return $this->relations[$propertyOrClassName] ?? $this->findRelationWithClass($propertyOrClassName);
    }

    private function findRelationWithClass(string $className): Relation
    {
        if ($relations = $this->relationClassNameMap[$className] ?? null) {
            if (1 !== \count($relations)) {
                throw new PropertyError(\sprintf("There is more than one relations using the class %s", $className));
            }
            return $this->relations[$relations[0]] ?? $this->relationDoesNotExists($className);
        }
        return $this->relationDoesNotExists($className);
    }

    private function doFindRelationWithPropertyName(string $propertyName): Relation
    {
        return $this->relationDoesNotExists($propertyName);
    }

    private function relationDoesNotExists(string $className): Relation
    {
        throw new \InvalidArgumentException(\sprintf(
            "Repository for %s has no relation with '%s' target class name or entity property name.",
            $this->className, $className
        ));
    }
}
