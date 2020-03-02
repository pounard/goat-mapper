<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Error\EntityDoesNotExistError;
use Goat\Mapper\Query\EntityQueryBuilder;
use Goat\Runner\Runner;

/**
 * @var Repository<T>
 */
class DefaultRepository implements Repository
{
    /** @var RepositoryDefinition */
    private $definition;

    /** @var RepositoryManager */
    private $manager;

    public function __construct(RepositoryDefinition $definition, RepositoryManager $manager)
    {
        $this->definition = $definition;
        $this->manager = $manager;
    }

    protected final function getRepositoryManager(): RepositoryManager
    {
        return $this->manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getRunner(): Runner
    {
        return $this->manager->getRunner();
    }

    /**
     * {@inheritdoc}
     */
    public final function getRepositoryDefinition(): RepositoryDefinition
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public final function getRelatedRepository(string $relation): Repository
    {
        return $this->manager->getRepository(
            $this->definition->getRelation($relation)->getClassName()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function query(): EntityQueryBuilder
    {
        return new EntityQueryBuilder($this);
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($id)
    {
        $query = $this->query()->fetch();

        $expandedId = EntityQueryBuilder::expandKey(
            $this->definition->getPrimaryKey(),
            $id,
            $query->getPrimaryTableAlias()
        );

        foreach ($expandedId as $property => $value) {
            $query->condition($property, $value);
        }

        $entity = $query->execute()->fetch();

        if (!$entity) {
            throw new EntityDoesNotExistError();
        }

        return $entity;
    }
}
