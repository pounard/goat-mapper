<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Error\ConfigurationError;

/**
 * Represent an entity.
 *
 * Repository is a transcient concept for us, it may, or may not exists, the
 * entity relation graph is sufficient for builder SQL queries and hydrate
 * classes.
 *
 * Repository is to be implemented only in case the using application needs
 * to create typed interfaces, and decouple this component from its business.
 */
final class EntityProxy implements Entity
{
    private string $className;
    private ?Entity $decorated = null;
    /** @var null|callable */
    private $initializer;

    public function __construct(string $className, callable $initializer)
    {
        $this->className = $className;
        $this->initializer = $initializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Initialize decorated entity.
     */
    private function doInitializeDecoratedEntity(): Entity
    {
        if (!$this->initializer) {
            throw new ConfigurationError(\sprintf(
                "Broken entity definition for %s.",
                $this->className
            ));
        }

        try {
            $entity = \call_user_func($this->initializer);

            if (!$entity instanceof Entity) {
                throw new ConfigurationError(\sprintf(
                    "Entity returned by initializer is not a %s instance.",
                    Entity::class
                ));
            }
            if ($entity->getClassName() !== $this->className) {
                throw new ConfigurationError(\sprintf(
                    "Entity returned by initializer is %s instead of %s",
                    $entity->getClassName(),
                    $this->className
                ));
            }

            return $entity;

        } finally {
            $this->initializer = null;
        }
    }

    /**
     * Get decorated entity.
     *
     * @internal
     *   Please do not use this outside of tests or this class.
     */
    public function getDecoratedEntity(): Entity
    {
        return $this->decorated ?? (
            $this->decorated = $this->doInitializeDecoratedEntity()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getInternalId(): string
    {
        return $this->getDecoratedEntity()->getInternalId();
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(): Table
    {
        return $this->getDecoratedEntity()->getTable();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey(): ?PrimaryKey
    {
        return $this->getDecoratedEntity()->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function hasRelations(): bool
    {
        return $this->getDecoratedEntity()->hasRelations();
    }

    /**
     * {@inheritdoc}
     */
    public function getRelation(string $propertyOrClassName): Relation
    {
        return $this->getDecoratedEntity()->getRelation($propertyOrClassName);
    }

    /**
     * {@inheritdoc}
     */
    public function getRelations(): iterable
    {
        return $this->getDecoratedEntity()->getRelations();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): iterable
    {
        return $this->getDecoratedEntity()->getProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(string $propertyName): ?string
    {
        return $this->getDecoratedEntity()->getColumn($propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnMap(): array
    {
        return $this->getDecoratedEntity()->getProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): iterable
    {
        return $this->getDecoratedEntity()->getChildren();
    }
}
