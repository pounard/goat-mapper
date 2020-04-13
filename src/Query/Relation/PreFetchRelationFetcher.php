<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Definition\IdentifierList;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Hydration\Collection\Collection;
use Goat\Mapper\Hydration\Collection\DefaultCollection;

final class PreFetchRelationFetcher implements RelationFetcher
{
    private RelationFetcher $decorated;
    private IdentifierList $identifierList;

    /** @var array<string,array<string,ResultSet>> */
    private array $cache = [];

    public function __construct(RelationFetcher $decorated, IdentifierList $identifierList)
    {
        $this->decorated = $decorated;
        $this->identifierList = $identifierList;
    }

    /**
     * {@inheritdoc}
     */
    public function single(string $className, string $propertyName, Identifier $id): ?object
    {
        return $this->getResult($className, $propertyName)->first($id);
    }

    /**
     * {@inheritdoc}
     */
    public function collection(string $className, string $propertyName, Identifier $id): Collection
    {
        if (!$this->identifierList->exists($id)) {
            $this->identifierDoesNotExists($id);
        }

        return new DefaultCollection(function () use ($className, $propertyName, $id) {
            return $this->getResult($className, $propertyName)->get($id);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function bulk(string $className, string $propertyName, array $identifiers): ResultSet
    {
        foreach ($identifiers as $id) {
            if (!$this->identifierList->exists($id)) {
                $this->identifierDoesNotExists($id);
            }
        }

        return $this->getResult($className, $propertyName);
    }

    private function doGetResult(string $className, string $propertyName): ResultSet
    {
        if ($this->identifierList->isEmpty()) {
            return new EmptyResultSet();
        }

        return $this->decorated->bulk($className, $propertyName, $this->identifierList->toArray());
    }

    private function getResult(string $className, string $propertyName)
    {
        return $this->cache[$className][$propertyName] ?? (
            $this->cache[$className][$propertyName] = $this->doGetResult($className, $propertyName)
        );
    }

    private function identifierDoesNotExists(Identifier $id): void
    {
        throw new QueryError(\sprintf("Identifier %s is not set for pre-fetch", $id->toString()));
    }
}
