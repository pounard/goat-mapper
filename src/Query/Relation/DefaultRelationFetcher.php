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
            ->related($className, $propertyName, [$id])
            ->build()
            ->range(0, 1)
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
                ->queryBuilderFactory
                ->related($className, $propertyName, [$id])
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
        // @todo ugly version that does not bulk load
        $ret = new DefaultResultSet();

        foreach ($identifiers as $id) {
            $ret->add(
                $id,
                $this->collection(
                    $className,
                    $propertyName,
                    $id
                )
            );
        }

        return $ret;

        /*
        $result = $this
            ->queryBuilderFactory
            ->related($className, $propertyName, $identifiers)
            ->execute()
        ;

        $rowCount = $result->countRows();

        if (!$rowCount) {
            return new EmptyResultSet();
        }

        if (1 === $rowCount) {
            return new SingleResultSet($result->fetch());
        }

        return new DefaultResultSet();
         */
    }
}
