<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Error\PropertyError;

class DefaultEntity implements Entity
{
    private string $className;
    private Table $table;
    private ?PrimaryKey $primaryKey;
    /** @var array<string,Property> */
    private ?array $properties = null;
    /** @var array<string,Relation> */
    private ?array $relations = null;
    /** @var array<string,Relation> */
    private array $relationClassNameMap = [];
    /** @var array<string,string[]> */
    private ?array $columnMapCache;

    /** @param null|Property[] $properties */
    public function __construct(string $className, Table $table, ?PrimaryKey $primaryKey, ?array $properties = null)
    {
        $this->className = $className;
        $this->table = $table;
        $this->primaryKey = $primaryKey;

        if (null !== $properties) {
            $this->setProperties($properties);
        }
    }

    /**
     * Allow lazy property set, when building object graph.
     *
     * @param Property[] $properties
     */
    public function setProperties(array $properties = []): void
    {
        if (null !== $this->properties) {
            throw new ConfigurationError("Properties are already set.");
        }

        $this->properties = [];
        $this->relations = [];

        foreach ($properties as $property) {
            \assert($property instanceof Property);

            $propertyName = $property->getName();
            $this->properties[$propertyName] = $property;

            if ($property instanceof Relation) {
                $this->relations[$propertyName] = $property;
                $this->relationClassNameMap[$property->getEntity()->getClassName()][] = $propertyName;
            }
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
     * {@inheritdoc}
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey(): ?PrimaryKey
    {
        return $this->primaryKey;
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
        return $this->relations ?? $this->propertiesAreNotInitialized();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): iterable
    {
        return $this->properties ?? $this->propertiesAreNotInitialized();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(string $propertyName): ?string
    {
        if (null === $this->columnMapCache) {
            $this->columnMapCache = $this->createColumnMap();
        }

        return $this->columnMapCache[$propertyName] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnMap(): array
    {
        return $this->columnMapCache ?? ($this->columnMapCache = $this->createColumnMap());
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): iterable
    {
        return $this->properties;
    }

    private function createColumnMap(): array
    {
        $ret = [];
        foreach ($this->properties as $property) {
            if ($property instanceof Value) {
                $ret[$property->getName()] = $property->getColumnName();
            }
        }

        return $ret;
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

    private function propertiesAreNotInitialized(): Relation
    {
        throw new PropertyError(\sprintf(
            "Repository for %s properties are not initialized.",
            $this->className
        ));
    }

    private function propertyDoesNotExist(string $propertyName): Relation
    {
        throw new PropertyError(\sprintf(
            "Repository for %s has no property '%s'.",
            $this->className, $propertyName
        ));
    }

    private function relationDoesNotExist(string $className): Relation
    {
        throw new PropertyError(\sprintf(
            "Repository for %s has no relation with '%s' target class name or entity property name.",
            $this->className, $className
        ));
    }
}
