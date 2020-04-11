<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder;

use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Builder\Relation\AnyToOneDefinitionBuilder;
use Goat\Mapper\Definition\Builder\Relation\ManyToManyDefinitionBuilder;
use Goat\Mapper\Definition\Builder\Relation\OneToManyDefinitionBuilder;
use Goat\Mapper\Definition\Builder\Relation\RelationDefinitionBuilder;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\Impl\DefaultEntity;
use Goat\Mapper\Definition\Graph\Impl\DefaultValue;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\ConfigurationError;

/**
 * Build definition for an entity.
 */
final class DefinitionBuilder
{
    use BuilderTrait;

    private string $className;
    private ?Table $table = null;
    private ?PrimaryKey $primaryKey = null;
    /** @var array<string,string> */
    private array $primaryKeyColumnMap = [];
    /** @var array<string,string> */
    private array $columnMap = [];
    /** @var array<string,RelationDefinitionBuilder> */
    private array $relationBuilders = [];

    public function __construct(string $className)
    {
        if (!\class_exists($className)) {
            throw new ConfigurationError(\sprintf("Class '%s' does not exist", $className));
        }
        $this->className = $className;
    }

    /**
     * Set SQL target table name.
     *
     * @param string $tableName
     *   SQL table name.
     * @param string $schema
     *   SQL schema name the table is within if different from default.
     */
    public function setTableName(string $tableName, ?string $schema = null): void
    {
        $this->table = new Table($tableName, $schema) ;
    }

    /**
     * Add a property to column mapping.
     *
     * @param string $propertyName
     *   PHP class property name.
     * @param string $columnName
     *   SQL column name if different from property name.
     */
    public function addProperty(string $propertyName, ?string $columnName = null): void
    {
        // @todo ensure property exists?
        $this->columnMap[$propertyName] = $columnName ?? $propertyName;
    }

    /**
     * Set primary key names
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setPrimaryKey(array $propertyTypeMap): void
    {
        if ($this->primaryKey) {
            throw new ConfigurationError("Primary key was already compiled, you cannot change it.");
        }

        $this->ensureKeyIsValid($propertyTypeMap);

        $this->primaryKeyColumnMap = $propertyTypeMap;
    }

    private function ensurePropertyCanHandleRelation(string $propertyName): void
    {
        if (isset($this->relationBuilders[$propertyName])) {
            throw new ConfigurationError(\sprintf("A relation on property '%s' is already defined.", $propertyName));
        }
        if (isset($this->columnMap[$propertyName])) {
            throw new ConfigurationError(\sprintf("A property with name '%s' already exist and is not a relation.", $propertyName));
        }
    }

    public function addAnyToOneRelation(
        string $propertyName,
        string $className,
        int $mode = Relation::MODE_MANY_TO_ONE
    ): AnyToOneDefinitionBuilder {
        $this->ensurePropertyCanHandleRelation($propertyName);

        return $this->relationBuilders[$propertyName] = new AnyToOneDefinitionBuilder(
            $propertyName,
            $className,
            $mode
        );
    }

    public function addOneToManyRelation(
        string $propertyName,
        string $className
    ): OneToManyDefinitionBuilder {
        $this->ensurePropertyCanHandleRelation($propertyName);

        return $this->relationBuilders[$propertyName] = new OneToManyDefinitionBuilder(
            $propertyName,
            $className
        );
    }

    public function addManyToManyRelation(
        string $propertyName,
        string $className
    ): ManyToManyDefinitionBuilder {
        $this->ensurePropertyCanHandleRelation($propertyName);

        throw new \Exception("Not implemeted yet.");

        return $this->relationBuilders[$propertyName] = new ManyToManyDefinitionBuilder(
            $propertyName,
            $className
        );
    }

    private function compilePrimaryKey(): PrimaryKey
    {
        return $this->primaryKey ?? (
            $this->primaryKey = $this->doCompilePrimaryKey(
                $this->primaryKeyColumnMap, $this->columnMap
            )
        );
    }

    private function compileTable(): Table
    {
        if (!$this->table) {
            $this->table = new Table($this->normalizeName($this->className));
        }

        return $this->table;
    }

    private function compileRelations(DefinitionRegistry $definitionRegistry): array
    {
        $ret = [];
        foreach ($this->relationBuilders as $relationBuilder) {
            \assert($relationBuilder instanceof RelationDefinitionBuilder);

            $ret[] = $relationBuilder->compile($definitionRegistry);
        }

        return $ret;
    }

    private function compileProperties(DefinitionRegistry $definitionRegistry, Entity $owner): array
    {
        $ret = [];
        foreach ($this->columnMap as $propertyName => $columnName) {
            $property = new DefaultValue($propertyName, $columnName, null);
            $property->setOwner($owner);
            $ret[] = $property;
        }
        foreach ($this->compileRelations($definitionRegistry) as $relation) {
            $relation->setOwner($owner);
            $ret[] = $relation;
        }

        return $ret;
    }

    /**
     * Compile and get the fully working property name.
     */
    public function compile(DefinitionRegistry $definitionRegistry): Entity
    {
        $entity = new DefaultEntity($this->className);
        $entity->setTable($this->compileTable());
        $entity->setPrimaryKey($this->compilePrimaryKey($entity));

        $entity->setProperties(
            $this->compileProperties($definitionRegistry, $entity)
        );

        return $entity;
    }
}
