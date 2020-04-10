<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Builder\BuilderTrait;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationManyToMany;

final class ManyToManyDefinitionBuilder implements RelationDefinitionBuilder
{
    use BuilderTrait;

    private string $sourcePropertyName;
    private string $targetClassName;
    private int $mode;
    private ?Table $targetTable = null;
    /** @var callable */
    private $sourceTable;
    /** @var callable */
    private $sourcePrimaryKey;
    /** @var null|array<string,string> */
    private ?array $sourceKey = null;
    /** @var null|array<string,string> */
    private array $targetKey = [];
    /** @var array<string,string> */
    private array $columnMap = [];

    public function __construct(
        string $sourcePropertyName,
        string $targetClassName,
        callable $sourceTable,
        callable $sourcePrimaryKey
    ) {
        $this->ensureClassExists($targetClassName);

        $this->targetClassName = $targetClassName;
        $this->sourcePrimaryKey = $sourcePrimaryKey;
        $this->sourcePropertyName = $sourcePropertyName;
        $this->sourceTable = $sourceTable;
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

    private function compileTargetKey(): Key
    {
        if (null === $this->targetKey) {
            return null;
        }

        return $this->doCompileKey($this->targetKey);
    }

    private function compileSourceKey(): Key
    {
        if (null === $this->sourceKey) {
            return null;
        }

        return $this->doCompileKey($this->sourceKey);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Entity $owner): Relation
    {
        return new RelationManyToMany(
            $owner,
            $this->sourcePropertyName,
            null /* $mapping */,
            $this->compileSourceKey(),
            $this->compileTargetKey()
        );
    }
}
