<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository;

use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Query\Entity\QueryBuilderFactory;
use Goat\Runner\Runner;

interface RepositoryManager
{
    /**
     * Get SQL query runner
     */
    public function getRunner(): Runner;

    /**
     * Fetch repository for class.
     *
     * @param string $name
     *   Target class name or repository alias, identifier or class name. If
     *   more than one repository matches the class name, an exception will
     *   be thrown.
     * @todo Give the possibility to mark a repository as being "primary"
     *   for a certain class name.
     */
    public function getRepository(string $className): Repository;

    /**
     * Get definition registry.
     */
    public function getDefinitionRegistry(): DefinitionRegistry;

    /**
     * Create an arbitrary entity query builder.
     */
    public function getQueryBuilderFactory(): QueryBuilderFactory;
}
