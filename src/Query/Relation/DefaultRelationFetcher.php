<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Hydration\Collection\Collection;
use Goat\Mapper\Hydration\Collection\CollectionInitializerResult;
use Goat\Mapper\Hydration\Collection\DefaultCollection;
use Goat\Mapper\Query\Entity\QueryBuilderFactory;

final class DefaultRelationFetcher implements RelationFetcher
{
    private QueryBuilderFactory $queryBuilderFactory;

    public function __construct(QueryBuilderFactory $queryBuilderFactory)
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function single(string $className, string $propertyName, Identifier $id): ?object
    {
        return $this
            ->queryBuilderFactory
            ->createFetchRelatedQuery($className, $propertyName, [$id])
            ->execute()
            ->fetch()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function collection(string $className, string $propertyName, Identifier $id): Collection
    {
        return new DefaultCollection(function () use ($className, $propertyName, $id) {
            $result = $this
                ->relationQueryBuilder
                ->createFetchRelatedQuery($className, $propertyName, [$id])
                ->execute()
            ;

            return new CollectionInitializerResult(
                $result,
                $result->countRows()
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function bulk(string $className, string $propertyName, array $identifiers): ResultSet
    {
        $result = $this
            ->relationQueryBuilder
            ->createFetchRelatedQuery($className, $propertyName, $identifiers)
            ->execute()
        ;

        if (!$result->countRows()) {
            return new EmptyResultSet();
        }

        if (1 === count($identifiers)) {
            return new SingleResultSet($result->fetch());
        }

        throw new \Exception("Not implemented yet");
    }
}
