<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Error\IncompleteObjectInitializationError;

abstract class AbstractRelation extends AbstractProperty implements Relation
{
    private int $mode;
    private string $className;
    private ?Entity $entity = null;
    private ?Key $sourceKey = null;
    private ?Key $targetKey = null;

    public function __construct(Entity $entity, string $name, string $className, int $mode)
    {
        if ($mode < 1 || 4 < $mode) {
            throw new ConfigurationError(\sprintf("Mode must be one of the %s::MODE_* constants.", __CLASS__));
        }

        parent::__construct($name);

        $this->className = $className;
        $this->entity = $entity;
        $this->mode = $mode;
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
    final public function getInverseRelation(): Relation
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * {@inheritdoc}
     */
    final public function isMultiple(): bool
    {
        return $this->mode === self::MODE_MANY_TO_MANY || $this->mode === self::MODE_ONE_TO_MANY;
    }

    /**
     * {@inheritdoc}
     */
    public function doEagerLoad(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    final public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    final public function getEntity(): Entity
    {
        return $this->entity ?? $this->entityIsNotSet();
    }

    /**
     * {@inheritdoc}
     */
    final public function getChildren(): iterable
    {
        return [$this->entity];
    }

    /**
     * Set source key
     */
    final public function setSourceKey(Key $sourceKey): void
    {
        $this->sourceKey = $sourceKey;
    }

    /**
     * {@inheritdoc}
     */
    final public function hasSourceKey(): bool
    {
        return null !== $this->sourceKey;
    }

    /**
     * {@inheritdoc}
     */
    final public function getSourceKey(): Key
    {
        return $this->sourceKey ?? $this->findDefaultSourceKey();
    }

    /**
     * Override me if necessary.
     */
    protected function findDefaultSourceKey(): Key
    {
        throw new IncompleteObjectInitializationError("Source key is missing from definition.");
    }

    /**
     * Set source key
     */
    final public function setTargetKey(Key $targetKey): void
    {
        $this->targetKey = $targetKey;
    }

    /**
     * {@inheritdoc}
     */
    final public function hasTargetKey(): bool
    {
        return null !== $this->targetKey;
    }

    /**
     * {@inheritdoc}
     */
    final public function getTargetKey(): Key
    {
        return $this->targetKey ?? $this->findDefaultTargetKey();
    }

    /**
     * Override me if necessary.
     */
    protected function findDefaultTargetKey(): Key
    {
        throw new ConfigurationError("Target key is missing from definition.");
    }

    /**
     * Entity was not set.
     */
    private function entityIsNotSet(): void
    {
        throw new IncompleteObjectInitializationError("Entity is missing from definition.");
    }
}
