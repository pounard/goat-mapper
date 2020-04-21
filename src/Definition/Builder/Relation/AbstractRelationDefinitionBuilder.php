<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Builder\BuilderTrait;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\IncompleteObjectInitializationError;

abstract class AbstractRelationDefinitionBuilder implements RelationDefinitionBuilder
{
    use BuilderTrait;

    protected string $sourcePropertyName;
    protected string $targetClassName;
    private int $mode;
    private ?Key $targetKey = null;
    private ?Key $sourceKey = null;

    public function __construct(string $sourcePropertyName, string $targetClassName, int $mode)
    {
        $this->ensureClassExists($targetClassName);

        $this->mode = $mode;
        $this->sourcePropertyName = $sourcePropertyName;
        $this->targetClassName = $targetClassName;
    }

    /**
     * Create your relation from here.
     */
    protected abstract function doCompile(Entity $entity): Relation;

    /**
     * {@inheritdoc}
     */
    final public function compile(DefinitionRegistry $definitionRegistry): Relation
    {
        $entity = $this->createProxy($this->targetClassName, $definitionRegistry);
        $relation = $this->doCompile($entity);

        return $relation;
    }

    /**
     * Set source key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    final protected function doSetSourceKey(array $propertyTypeMap): void
    {
        $this->ensureKeyIsValid($propertyTypeMap);

        // @todo propertyNames to column_names conversion here.
        $this->sourceKey = $this->doCompileKey($propertyTypeMap);
    }

    /**
     * Set target key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    final protected function doSetTargetKey(array $propertyTypeMap): void
    {
        $this->ensureKeyIsValid($propertyTypeMap);

        // @todo propertyNames to column_names conversion here.
        $this->targetKey = $this->doCompileKey($propertyTypeMap);
    }

    /**
     * Compile source key.
     */
    final protected function compileSourceKey(bool $required = false): ?Key
    {
        if (null === $this->sourceKey) {
            if ($required) {
                throw new IncompleteObjectInitializationError(\sprintf(
                    "Relation for property '%s' source key (target identifier in source table) must be specified using %s::setSourceKey().",
                    $this->sourcePropertyName,
                    __CLASS__
                ));
            }

            return null;
        }

        if ($this->targetKey) {
            $this->ensureKeysCompatibility($this->sourceKey, $this->targetKey);
        }

        return $this->sourceKey;
    }

    /**
     * Compile target key.
     */
    final protected function compileTargetKey(bool $required = false): ?Key
    {
        if (null === $this->targetKey) {
            if ($required) {
                throw new IncompleteObjectInitializationError(\sprintf(
                    "Relation for property '%s' target key (source identifier in target table) must be specified using %s::setTargetKey().",
                    $this->sourcePropertyName,
                    __CLASS__
                ));
            }

            return null;
        }

        if ($this->sourceKey) {
            $this->ensureKeysCompatibility($this->sourceKey, $this->targetKey);
        }

        return $this->targetKey;
    }
}
