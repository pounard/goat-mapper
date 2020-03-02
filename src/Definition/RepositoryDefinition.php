<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

use Goat\Mapper\Error\RelationDoesNotExistError;
use Goat\Mapper\Error\PropertyError;

/**
 * @todo
 *   - column to object property mapping (with nested object support),
 *   - a builder object/pattern for people that want to use plain PHP to define a repository,
 *   - repository with different tables to JOIN
 */
class RepositoryDefinition
{
    /** @var string */
    private $className;

    /** @var EntityDefinition */
    private $entityDefinition;

    /** @var Table */
    private $table;

    /** @var PrimaryKey */
    private $primaryKey;

    /** @var Relation[] */
    private $relations = [];

    /**
     * @var array>string,string[]>
     *   Keys are class names, values are array of relation identifiers.
     */
    private $relationClassNameMap = [];

    /** @param Relation[] $relations */
    public function __construct(EntityDefinition $entityDefinition, Table $table, PrimaryKey $primaryKey, array $relations = [])
    {
        $this->entityDefinition = $entityDefinition;
        $this->primaryKey = $primaryKey;
        $this->table = $table;

        $className = $entityDefinition->getClassName();

        foreach ($relations as $relation) {
            \assert($relation instanceof Relation);

            if (!$relation->getKey()->isCompatible($this->primaryKey)) {
                throw new \InvalidArgumentException(\sprintf(
                    "Relation to %s key is incompible with %s primary key.",
                    $relation->getClassName(), $className
                ));
            }

            $propertyName = $relation->getPropertyName();
            if (isset($this->relations[$propertyName])) {
                throw new PropertyError(\sprintf("A relation on property '%s' already exists",  $propertyName));
            }
            $this->relations[$propertyName] = $relation;

            $this->relationClassNameMap[$relation->getClassName()][] = $propertyName;
        }
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->entityDefinition;
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

    /**
     * Find appropriate column name for given property name.
     *
     * If nothing found, it returns null, in opposition to relation, it is a
     * non-blocking error not being able to find a property, SQL query builder
     * will pass on the user-provided arbitrary column name that might exist in
     * schema while not being an entity-defined property.
     */
    public function findColumnName(string $propertyName): ?string
    {
        return $this->entityDefinition->getColumn($propertyName);
    }

    private function doFindRelationWithPropertyName(string $propertyName): Relation
    {
        return $this->relationDoesNotExists($propertyName);
    }

    private function relationDoesNotExists(string $className): Relation
    {
        throw new \InvalidArgumentException(\sprintf(
            "Repository for %s has no relation with '%s' target class name or entity property name.",
            $this->entityDefinition->getClassName(), $className
        ));
    }
}
