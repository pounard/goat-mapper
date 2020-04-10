<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Builder\BuilderTrait;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationAnyToOne;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\ConfigurationError;

/**
 * Build definition for an entity.
 */
final class AnyToOneDefinitionBuilder implements RelationDefinitionBuilder
{
    use BuilderTrait;

    private string $sourcePropertyName;
    private string $targetClassName;
    private ?Key $targetKey = null;
    private ?Key $sourceKey = null;
    private int $mode = Relation::MODE_MANY_TO_ONE;

    public function __construct(string $sourcePropertyName, string $targetClassName, int $mode)
    {
        $this->ensureClassExists($targetClassName);

        $this->mode = $mode;
        $this->sourcePropertyName = $sourcePropertyName;
        $this->targetClassName = $targetClassName;
    }

    /**
     * Set source key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setSourceKey(array $propertyTypeMap): void
    {
        $this->ensureKeyIsValid($propertyTypeMap);

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
    public function setTargetKeyIfNotPrimaryKey(array $propertyTypeMap): void
    {
        $this->ensureKeyIsValid($propertyTypeMap);

        $this->targetKey = $this->doCompileKey($propertyTypeMap);
    }

    private function compileSourceKey(): Key
    {
        if (null === $this->sourceKey) {
            throw new ConfigurationError(\sprintf(
                "Relation for property '%s' source key (target identifier in source table) must be specified using %s::setTargetKey().",
                $this->sourcePropertyName,
                __CLASS__
            ));
        }

        return $this->sourceKey;
    }

    private function compileTargetKey(): ?Key
    {
        if ($this->targetKey) {
            $this->ensureKeysCompatibility($this->sourceKey, $this->targetKey);
        }

        return $this->targetKey;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(DefinitionRegistry $definitionRegistry): Relation
    {
        return new RelationAnyToOne(
            $this->createProxy($this->targetClassName, $definitionRegistry),
            $this->sourcePropertyName,
            $this->compileSourceKey(),
            $this->compileTargetKey(),
            $this->mode
        );
    }
}
