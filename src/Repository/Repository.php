<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Error\RelationDoesNotExistError;

/**
 * Use this interface to implement your repositories and make sure they will
 * be registered with others.
 *
 * You should probably start by extending the AbstractRepository class which
 * will give you the bare minimum implementation.
 *
 * @var Repository<T>
 */
interface Repository
{
    /**
     * Get repository definition.
     */
    public function getDefinition(): Entity;

    /**
     * Get repository for relation
     *
     * @param string $propertyOrClassName
     *   Anything that Entity::getRelation() accepts.
     * @see Entity::getRelation()
     *   For $relation parameter definition.
     * @throws RelationDoesNotExistError
     *   If property does not exist or is not a relation.
     */
    public function getRelatedRepository(string $propertyOrClassName): Repository;
}
