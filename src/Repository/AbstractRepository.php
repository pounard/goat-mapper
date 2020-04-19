<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository;

use Goat\Mapper\EntityManager;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Runner\Runner;

/**
 * @var Repository<T>
 */
abstract class AbstractRepository implements Repository
{
    private Entity $definition;
    private EntityManager $manager;
    private string $className;

    public function __construct(string $className, EntityManager $manager)
    {
        $this->definition = $manager->getDefinitionRegistry()->getDefinition($className);
        $this->className = $className;
        $this->manager = $manager;
    }

    /**
     * Get entity manager.
     */
    protected final function getEntityManager(): EntityManager
    {
        return $this->manager;
    }

    /**
     * Get database connection runner.
     */
    protected final function getRunner(): Runner
    {
        return $this->manager->getRunner();
    }

    /**
     * {@inheritdoc}
     */
    public final function getDefinition(): Entity
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public final function getRelatedRepository(string $propertyOrClassName): Repository
    {
        return $this->manager->getRepository(
            $this
                ->definition
                ->getRelation($propertyOrClassName)
                ->getClassName()
        );
    }

    /**
     * Create select query builder.
     */
    protected function query(?string $primaryTableAlias = null): EntityQuery
    {
        return $this
            ->manager
            ->query(
                $this->className,
                $primaryTableAlias
            )
        ;
    }
}
