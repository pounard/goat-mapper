<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Hydration\Collection\Collection;

final class PreFetchRelationFetcher implements RelationFetcher
{
    /** @var RelationFetcher */
    private $decorated;

    /** @var iterable<Identifier> */
    private $identifiers;

    /**
     * @var array<string,array<string,ResultSet>>
     * @todo Consider emptying this after everything has been fetched.
     */
    private $cache = [];

    /** @param iterable<Identifier> $identifiers */
    public function __construct(RelationFetcher $decorated, iterable $identifiers)
    {
        $this->decorated = $decorated;
        $this->identifiers = $identifiers;
    }

    private function identifierExists(Identifier $id): bool
    {
        foreach ($this->identifiers as $other) {
            if ($id->equals($other)) {
                return true;
            }
        }
        return false;
    }

    private function identifierDoesNotExists(Identifier $id): void
    {
        throw new QueryError(\sprintf("Identifier %s was not prefetched", $id));
    }

    private function doLoad(string $className, string $propertyName): ResultSet
    {
        return $this->decorated->bulk($className, $propertyName, $this->identifiers);
    }

    private function getResult(string $className, string $propertyName)
    {
        return $this->cache[$className][$propertyName] ?? (
            $this->cache[$className][$propertyName] = $this->doLoad($className, $propertyName)
        );
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
        if (!$this->identifierExists($id)) {
            $this->identifierDoesNotExists($id);
        }

        return $this->getResult($className, $propertyName)->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function bulk(string $className, string $propertyName, array $identifiers): ResultSet
    {
        foreach ($identifiers as $id) {
            if (!$this->identifierExists($id)) {
                $this->identifierDoesNotExists($id);
            }
        }

        return $this->getResult($className, $propertyName);
    }
}
