<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Error\RelationDoesNotExistError;
use Goat\Mapper\Query\Graph\EntityQuery;
use Goat\Runner\Runner;

/**
 * @var Repository<T>
 */
interface Repository
{
    /**
     * Get SQL query runner
     */
    public function getRunner(): Runner;

    /**
     * Get repository definition.
     */
    public function getDefinition(): RepositoryDefinition;

    /**
     * Get repository for relation
     *
     * @param string $relation
     *   Anything that RepositoryDefinition::getRelation() accepts.
     * @see RepositoryDefinition::getRelation()
     *   For $relation parameter definition.
     * @throws RelationDoesNotExistError
     *   If property does not exist or is not a relation.
     */
    public function getRelatedRepository(string $relation): Repository;

    /**
     * Create select query builder.
     */
    public function query(?string $primaryTableAlias = null): EntityQuery;

    /**
     * Find one entity.
     *
     * This method is useful generally for automatic entity loading such as
     * using Symfony's argument value resolver based upon request parameters.
     *
     * Repositories can override this method in order to support any other
     * key than primary key for loading (any unique key or other conditions
     * for example).
     *
     * @param mixed|mixed[]|Identifier $id
     *   Can be either of:
     *     - a single value, if primary key has one column,
     *     - an array with a single value, if primary key has one column,
     *     - an array of values, if primary key has many columns.
     *
     * @return <T>
     *   Loaded object with class T.
     *
     * @throws \Goat\Mapper\Error\EntityDoesNotExistError
     *   If entity was not found.
     */
    public function findOne($id);
}
