<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph;

use Goat\Mapper\Error\QueryError;

abstract class Node
{
    protected string $className;
    protected bool $doLoad = false;
    protected ?string $alias;
    protected string $path;
    private bool $atLeastOneChildMatches = false;
    private bool $doMatch = false;

    /** @var PropertyNode[] */
    protected array $children = [];

    /** @var array<string,array<mixed>> */
    private array $conditions = [];

    public function __construct(string $className)
    {
        $this->className = $className;
        $this->path = '<root>';
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /** @return PropertyNode[] */
    public function getChildren(): iterable
    {
        return $this->children;
    }

    /**
     * Get SQL table alias
     */
    public function getAlias(): string
    {
        if (!$this->alias) {
            throw new QueryError("Node at path '%s' has no alias.", $this->path);
        }

        return $this->alias;
    }

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    public function add(string $propertyName, string $className): PropertyNode
    {
        return $this->children[$propertyName] = new PropertyNode(
            $propertyName,
            $className,
            $propertyName
        );
    }

    public function get(string $propertyName): PropertyNode
    {
        return $this->children[$propertyName] ?? $this->childDoesNotExist($propertyName);
    }

    public function has(string $propertyName): bool
    {
        return isset($this->children[$propertyName]);
    }

    public function upsert(string $propertyName, string $className): PropertyNode
    {
        return $this->children[$propertyName] ?? $this->add($propertyName, $className);
    }

    /**
     * Mark this entity as being eargly loaded.
     */
    public function toggleLoad(bool $toggle = true): void
    {
        $this->doLoad = $toggle;
    }

    /**
     * Should this node column be loaded.
     *
     * This means that the entity will be eagerly selected and loaded.
     */
    public function shouldLoad(): bool
    {
        return $this->doLoad;
    }

    /**
     * Add conditions for loading this entity.
     *
     * If conditions are added, JOIN statements will be INNER JOIN and thus
     * will also condition load of its parents.
     *
     * @param string $propertyName
     *   This entity property name, cannot be a relation.
     * @param mixed $expression
     *   Any expression that goat-query can handle.
     */
    public function withCondition(string $propertyName, $expression): void
    {
        $this->conditions[$propertyName][] = $expression;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function toggleAnyChildShouldMatch(bool $toggle = true): void
    {
        $this->atLeastOneChildMatches = $toggle;
    }

    /**
     * Should this node conditions be matched.
     */
    public function shouldAnyChildThatMatch(): bool
    {
        return $this->atLeastOneChildMatches;
    }

    public function toggleMatch(bool $toggle = true): void
    {
        $this->doMatch = $toggle;
    }

    /**
     * Should this node conditions be matched.
     */
    public function shouldMatch(): bool
    {
        return $this->doMatch;
    }

    private function childDoesNotExist(string $propertyName): void
    {
        throw new QueryError(\sprintf(
            "Child with name %s does not exist in node %s",
            $propertyName,
            $this->path
        ));
    }
}
