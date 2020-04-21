<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Entity\QueryBuilderFactory;
use Goat\Mapper\Repository\Registry\RepositoryRegistry;
use Goat\Runner\Runner;

interface EntityManager extends RepositoryRegistry
{
    /**
     * Get SQL query runner
     */
    public function getRunner(): Runner;

    /**
     * Get definition registry.
     */
    public function getDefinitionRegistry(): DefinitionRegistry;

    /**
     * Create an arbitrary entity query builder.
     */
    public function getQueryBuilderFactory(): QueryBuilderFactory;

    /**
     * Create a query builder for entity.
     *
     * Alias to $this->getQueryBuilderFactory()->query($className);
     */
    public function query(string $className, ?string $primaryTableAlias = null): EntityQuery;
}
