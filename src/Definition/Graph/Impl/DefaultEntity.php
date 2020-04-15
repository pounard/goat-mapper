<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Property;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\Value;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Error\PropertyError;

final class DefaultEntity extends AbstractNode implements Entity
{
    private string $className;
    private ?Table $table = null;
    private ?PrimaryKey $primaryKey = null;
    /** @var array<string,Property> */
    private array $properties = [];
    /** @var array<string,Relation> */
    private array $relations = [];
    /** @var array<string,Relation> */
    private array $relationClassNameMap = [];
    /** @var array<string,string[]> */
    private array $columnMapCache = [];

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Allow lazy property set, when building object graph.
     *
     * @param Property[] $properties
     */
    public function setProperties(array $properties = []): void
    {
        foreach ($properties as $property) {
            $this->addProperty($property);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Set table.
     */
    public function setTable(Table $table): void
    {
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(): Table
    {
        return $this->table ?? $this->tableIsNotSet();
    }

    /**
     * Set primary key.
     */
    public function setPrimaryKey(PrimaryKey $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey(): ?PrimaryKey
    {
        return $this->primaryKey;
    }

    /**
     * Add a single property.
     */
    public function addProperty(Property $property): void
    {
        $propertyName = $property->getName();

        if (isset($this->properties[$propertyName])) {
            throw new ConfigurationError(\sprintf("Property %s was already set.", $propertyName));
        }

        if ($property instanceof Relation) {
            $this->relations[$propertyName] = $property;
            $this->relationClassNameMap[$property->getEntity()->getClassName()][] = $propertyName;
        } else if ($property instanceof Value) {
            $this->properties[$propertyName] = $property;
            $this->columnMapCache[$property->getName()] = $property->getColumnName();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRelation(string $propertyOrClassName): Relation
    {
        return $this->relations[$propertyOrClassName] ?? $this->findRelationWithClass($propertyOrClassName);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelations(): iterable
    {
        return $this->relations;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): iterable
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(string $propertyName): ?string
    {
        return $this->columnMapCache[$propertyName] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnMap(): array
    {
        return $this->columnMapCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): iterable
    {
        return $this->properties;
    }

    private function findRelationWithClass(string $className): Relation
    {
        if ($relations = $this->relationClassNameMap[$className] ?? null) {
            if (1 !== \count($relations)) {
                throw new PropertyError(\sprintf("There is more than one relations using the class %s", $className));
            }
            return $this->relations[$relations[0]] ?? $this->relationDoesNotExist($className);
        }
        return $this->relationDoesNotExist($className);
    }

    private function tableIsNotSet(): void
    {
        throw new ConfigurationError("Table is missing from definition.");
    }

    private function relationDoesNotExist(string $className): Relation
    {
        throw new PropertyError(\sprintf(
            "Repository for %s has no relation with '%s' target class name or entity property name.",
            $this->className, $className
        ));
    }
}
