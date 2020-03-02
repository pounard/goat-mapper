<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Mapper\Definition\DefinitionRegistry;
use Goat\Mapper\Error\RepositoryDoesNotExistError;
use Goat\Mapper\Query\EntityQueryBuilder;
use Goat\Runner\Runner;

class DefaultRepositoryManager implements RepositoryManager
{
    /** @var Runner */
    private $runner;

    /** @var DefinitionRegistry */
    private $definitionRegistry;

    /** @var Repository[] */
    private $repositories = [];

    public function __construct(Runner $runner, DefinitionRegistry $definitionRegistry)
    {
        $this->runner = $runner;
        $this->definitionRegistry = $definitionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getRunner(): Runner
    {
        return $this->runner;
    }

    /**
     * Fetch repository for class.
     *
     * @param string $className
     *   Target class name or repository alias, identifier or class name.
     *
     * @throws RepositoryDoesNotExistError
     *   If repository does not exists or if its definition is invalid.
     */
    public function getRepository(string $className): Repository
    {
        return $this->repositories[$className] ?? (
            $this->repositories[$className] = $this->createRepository($className)
        );
    }

    /**
     * Create an arbitrary entity query builder.
     */
    public function createEntityQueryBuilder(string $className): EntityQueryBuilder
    {
        return $this->getRepository($className)->query();
    }

    private function createRepository(string $className): Repository
    {
        // @todo Make it pluggage for custom implementation.
        return new DefaultRepository(
            $this->definitionRegistry->getRepositoryDefinition($className),
            $this
        );
    }
}
