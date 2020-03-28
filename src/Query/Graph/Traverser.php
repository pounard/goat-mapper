<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph;

use Goat\Mapper\Query\Graph\Visitor\EagerJoinVisitor;
use Goat\Mapper\Query\Graph\Visitor\MatchVisitor;
use Goat\Mapper\Query\Graph\Visitor\PropertyVisitor;
use Goat\Mapper\Query\Graph\Visitor\RootVisitor;
use Goat\Mapper\Query\Graph\Visitor\SelectColumnVisitor;
use Goat\Mapper\Query\Graph\Visitor\SourceJoinVisitor;
use Goat\Mapper\Query\Graph\Visitor\Visitor;

final class Traverser
{
    /** Traverser and visitors are supposed to be stateless. */
    private static ?self $queryBuilderInstance;

    /** @var Visitor[] */
    private iterable $propertyVisitors = [];
    /** @var Visitor[] */
    private iterable $rootVisitors = [];

    /** @param Visitor[] */
    public function __construct(iterable $visitors)
    {
        foreach ($visitors as $visitor) {
            if ($visitor instanceof PropertyVisitor) {
                $this->propertyVisitors[] = $visitor;
            }
            if ($visitor instanceof RootVisitor) {
                $this->rootVisitors[] = $visitor;
            }
        }
    }

    /**
     * Create instance that creates SQL queries.
     */
    public static function createQueryBuilder(): self
    {
        return self::$queryBuilderInstance ?? (
            self::$queryBuilderInstance = new self([
                new SelectColumnVisitor(),
                new EagerJoinVisitor(),
                new MatchVisitor(),
                new SourceJoinVisitor(),
            ])
        );
    }

    private function doTraverse(PropertyNode $node, Node $parent, EntityQuery $query): void
    {
        foreach ($this->propertyVisitors as $visitor) {
            \assert($visitor instanceof Visitor);

            $visitor->onPropertyNode($node, $parent, $query);
        }

        foreach ($node->getChildren() as $child) {
            $this->doTraverse($child, $node, $query);
        }
    }

    public function traverse(EntityQuery $query): void
    {
        $rootNode = $query->getRootNode();

        foreach ($this->rootVisitors as $visitor) {
            \assert($visitor instanceof Visitor);

            $visitor->onRootNode($rootNode, $query);
        }

        foreach ($rootNode->getChildren() as $child) {
            $this->doTraverse($child, $rootNode, $query);
        }
    }
}
