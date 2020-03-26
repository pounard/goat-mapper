<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder;

use Goat\Mapper\Definition\EntityDefinition;
use Goat\Mapper\Definition\Relation;
use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Definition\PrimaryKey;

/**
 * Build definition for an entity.
 */
final class DefinitionBuilder
{
    use BuilderTrait;

    /** @var string */
    private $className;

    /** @var null|Table */
    private $table;

    /** @var null|PrimaryKey */
    private $primaryKey;

    /** @var string */
    private $primaryKeyColumnMap = [];

    /** @var array<string,string> */
    private $columnMap = [];

    /** @var array<string,RelationDefinitionBuilder> */
    private $relationBuilders = [];

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

    private function createRelationBuilder(string $propertyName, string $className, int $mode): RelationDefinitionBuilder
    {
        return $this->relationBuilders[$propertyName] = new RelationDefinitionBuilder(
            $propertyName,
            $className,
            // Those methods are lazy, because we can't guarantee that the user
            // will call setTableName() or setPrimaryKey() before calling this
            // method.
            $this->lazy('compileTable'),
            $this->lazy('compilePrimaryKey'),
            $mode
        );
    }

    public function addOneToOneRelation(string $propertyName, string $className): RelationDefinitionBuilder
    {
        $this->ensurePropertyCanHandleRelation($propertyName);

        return $this->createRelationBuilder($propertyName, $className, Relation::MODE_ONE_TO_ONE);
    }

    public function addOneToManyRelation(string $propertyName, string $className): RelationDefinitionBuilder
    {
        $this->ensurePropertyCanHandleRelation($propertyName);

        return $this->createRelationBuilder($propertyName, $className, Relation::MODE_ONE_TO_MANY);
    }

    public function addManyToOneRelation(string $propertyName, string $className): RelationDefinitionBuilder
    {
        $this->ensurePropertyCanHandleRelation($propertyName);

        return $this->createRelationBuilder($propertyName, $className, Relation::MODE_MANY_TO_ONE);
    }

    public function addToManyRelation(string $propertyName, string $className): RelationDefinitionBuilder
    {
        $this->ensurePropertyCanHandleRelation($propertyName);

        return $this->createRelationBuilder($propertyName, $className, Relation::MODE_MANY_TO_MANY);
    }

    private function compileEntityDefinition(): EntityDefinition
    {
        return new EntityDefinition(
            $this->className,
            $this->columnMap
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

    private function compileRelations(): array
    {
        $ret = [];

        foreach ($this->relationBuilders as $relationBuilder) {
            \assert($relationBuilder instanceof RelationDefinitionBuilder);
            $ret[] = $relationBuilder->compile();
        }

        return $ret;
    }

    /**
     * Compile and get the fully working property name.
     */
    public function compile()
    {
        return new RepositoryDefinition(
            $this->compileEntityDefinition(),
            $this->compileTable(),
            $this->compilePrimaryKey(),
            $this->compileRelations(),
        );
    }
}
