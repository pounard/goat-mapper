<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository;

use Goat\Mapper\Error\EntityDoesNotExistError;
use Goat\Mapper\Query\Entity\QueryBuilderFactory;

/**
 * @var Repository<T>
 */
class DefaultRepository extends AbstractRepository
{
    /**
     * Find one entity.
     */
    public function findOne($id, bool $raiseErrorOnMissing = true)
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

        if ($entity) {
            return $entity;
        }

        if ($raiseErrorOnMissing) {
            throw new EntityDoesNotExistError();
        }

        return null;
    }
}
