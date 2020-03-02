<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Mapper\Query\EntityQueryBuilder;
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
     * Create an arbitrary entity query builder.
     */
    public function createEntityQueryBuilder(string $className): EntityQueryBuilder;
}
