<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Builder\BuilderTrait;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationOneToMany;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\ConfigurationError;

/**
 * Build definition for an entity.
 */
final class OneToManyDefinitionBuilder implements RelationDefinitionBuilder
{
    use BuilderTrait;

    private string $sourcePropertyName;
    private string $targetClassName;
    private ?Key $targetKey = null;
    private ?Key $sourceKey = null;
    /** @var callable */
    private $defaultSourceKey;

    public function __construct(string $sourcePropertyName, string $targetClassName)
    {
        $this->ensureClassExists($targetClassName);

        $this->sourcePropertyName = $sourcePropertyName;
        $this->targetClassName = $targetClassName;
    }

    /**
     * Set target key if different from primary key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setTargetKey(array $propertyTypeMap): void
    {
        $this->ensureKeyIsValid($propertyTypeMap);

        $this->targetKey = $this->doCompileKey($propertyTypeMap);
    }

    /**
     * Set source key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setSourceKeyIfNotPrimaryKey(array $propertyTypeMap): void
    {
        $this->ensureKeyIsValid($propertyTypeMap);

        $this->sourceKey = $this->doCompileKey($propertyTypeMap);
    }


    private function compileTargetKey(): Key
    {
        if (null === $this->targetKey) {
            throw new ConfigurationError(\sprintf(
                "Relation for property '%s' target key (source identifier in target table) must be specified using %s::setTargetKey().",
                $this->sourcePropertyName,
                __CLASS__
            ));
        }

        return $this->targetKey;
    }

    private function compileSourceKey(): ?Key
    {
        if ($this->sourceKey) {
            $this->ensureKeysCompatibility($this->sourceKey, $this->targetKey);
        }

        return $this->sourceKey;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(DefinitionRegistry $definitionRegistry): Relation
    {
        return new RelationOneToMany(
            $this->createProxy($this->targetClassName, $definitionRegistry),
            $this->sourcePropertyName,
            $this->compileTargetKey(),
            $this->compileSourceKey()
        );
    }
}
