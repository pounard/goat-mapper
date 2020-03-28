<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository;

use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Error\EntityDoesNotExistError;
use Goat\Mapper\Query\Entity\EntitySelectQuery;
use Goat\Mapper\Query\Entity\QueryBuilderFactory;
use Goat\Mapper\Query\Graph\EntityQuery;
use Goat\Runner\Runner;

/**
 * @var Repository<T>
 */
class DefaultRepository implements Repository
{
    private RepositoryDefinition $definition;
    private RepositoryManager $manager;
    private string $className;

    public function __construct(RepositoryDefinition $definition, RepositoryManager $manager)
    {
        $this->className = $definition->getClassName();
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
    public final function getDefinition(): RepositoryDefinition
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public final function getRelatedRepository(string $relation): Repository
    {
        return $this->manager->getRepository(
            $this
                ->definition
                ->getRelation($relation)
                ->getClassName()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(?string $primaryTableAlias = null): EntitySelectQuery
    {
        return $this
            ->manager
            ->getQueryBuilderFactory()
            ->select(
                $this->className,
                $primaryTableAlias
            )
        ;
    }

    /**
     * Create select query builder.
     */
    public function query(?string $primaryTableAlias = null): EntityQuery
    {
        return $this
            ->manager
            ->getQueryBuilderFactory()
            ->query(
                $this->className,
                $primaryTableAlias
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($id)
    {
        $query = $this->fetch();

        $expandedId = QueryBuilderFactory::expandKey(
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
