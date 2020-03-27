<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Relation;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Error\ConfigurationError;

/**
 * Build definition for an entity.
 */
final class RelationDefinitionBuilder
{
    use BuilderTrait;

    /** @var string */
    private $sourcePropertyName;

    /** @var string */
    private $targetClassName;

    /** @var int */
    private $mode;

    /** @var null|int */
    private $keyIn;

    /** @var callable */
    private $sourceTable;

    /** @var callable */
    private $sourcePrimaryKey;

    /** @var null|Table */
    private $targetTable;

    /** @var null|array<string,string> */
    private $sourceKey;

    /** @var null|array<string,string> */
    private $targetKey = [];

    /** @var array<string,string> */
    private $columnMap = [];

    public function __construct(
        string $sourcePropertyName,
        string $targetClassName,
        callable $sourceTable,
        callable $sourcePrimaryKey,
        int $mode
    ) {
        if (!\class_exists($targetClassName)) {
            throw new ConfigurationError(\sprintf("Class '%s' does not exist", $targetClassName));
        }

        $this->mode = $mode;
        $this->sourcePrimaryKey = $sourcePrimaryKey;
        $this->sourcePropertyName = $sourcePropertyName;
        $this->sourceTable = $sourceTable;
        $this->targetClassName = $targetClassName;

        switch ($mode) {
            case Relation::MODE_MANY_TO_MANY:
                $this->keyIn = Relation::KEY_IN_MAPPING;
                break;

            case Relation::MODE_MANY_TO_ONE:
                $this->keyIn = Relation::KEY_IN_TARGET;
                break;

            case Relation::MODE_ONE_TO_MANY:
                $this->keyIn = Relation::KEY_IN_SOURCE;
                break;

            case Relation::MODE_ONE_TO_ONE:
                // This seems like a sensible default, but you have 50%
                // chances to find the key in the target table as well.
                // It's an arbitrary decision that this is the default.
                $this->keyIn = Relation::KEY_IN_SOURCE;
                break;
        }
    }

    /**
     * Foreign key is in the source table.
     */
    public function keyIsInSourceTable(): void
    {
        if (Relation::MODE_MANY_TO_MANY === $this->mode || Relation::MODE_ONE_TO_MANY === $this->mode) {
            throw new ConfigurationError("Relation key cannot be in source for any to many relations.");
        }
        $this->keyIn = Relation::KEY_IN_SOURCE;
    }

    /**
     * Foreign key is in the target table.
     */
    public function keyIsInTargetTable(): void
    {
        if (Relation::MODE_MANY_TO_MANY === $this->mode || Relation::MODE_MANY_TO_ONE === $this->mode) {
            throw new ConfigurationError("Relation key cannot be in target for many to any relations.");
        }
        $this->keyIn = Relation::KEY_IN_TARGET;
    }

    /**
     * Foreign key is in a mapping table.
     */
    public function keyIsInMappingTable(): void
    {
        $this->keyIn = Relation::KEY_IN_MAPPING;
    }

    /**
     * Set SQL target table name.
     *
     * @param string $tableName
     *   SQL table name.
     * @param string $schema
     *   SQL schema name the table is within if different from default.
     */
    public function setTargetTableName(string $tableName, ?string $schema = null): void
    {
        $this->targetTable = new Table($tableName, $schema);
    }

    /**
     * Set target key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setTargetKey(array $propertyTypeMap): void
    {
        $this->ensureKeyIsValid($propertyTypeMap);

        $this->targetKey = $propertyTypeMap;
    }

    /**
     * Set source key if different from primary key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setSourceKey(array $propertyTypeMap): void
    {
        $this->ensureKeyIsValid($propertyTypeMap);

        $this->sourceKey = $propertyTypeMap;
    }

    private function compileTargetTable(): Table
    {
        if (null === $this->targetTable) {
            throw new ConfigurationError(\sprintf(
                "Relation for property '%s' is missing the target table.",
                $this->sourcePropertyName
            ));
        }

        return $this->targetTable;
    }

    private function compileTargetKey(): Key
    {
        if (null === $this->targetKey) {
            throw new ConfigurationError(\sprintf(
                "Relation for property '%s' target table key must be specified using %s::setSourceKey().",
                $this->sourcePropertyName,
                __CLASS__
            ));
        }

        return $this->doCompileKey($this->targetKey);
    }

    private function compileSourceTable(): Table
    {
        return \call_user_func($this->sourceTable);
    }

    private function compileSourceKey(): Key
    {
        if (null === $this->sourceKey) {
            if ($this->keyIn === Relation::KEY_IN_SOURCE) {
                throw new ConfigurationError(\sprintf(
                    "Relation for property '%s' if key is in source table, it must be specified using %s::setSourceKey().",
                    $this->sourcePropertyName,
                    __CLASS__
                ));
            }

            return \call_user_func($this->sourcePrimaryKey);
        }

        return $this->doCompileKey($this->sourceKey);
    }

    /**
     * Compile and get the fully working property name.
     */
    public function compile()
    {
        return new Relation(
            $this->sourcePropertyName,
            $this->targetClassName,
            $this->mode,
            $this->compileTargetTable(),
            $this->compileSourceTable(),
            $this->compileTargetKey(),
            $this->compileSourceKey(),
            $this->keyIn
        );
    }
}
