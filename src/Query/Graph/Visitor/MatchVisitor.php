<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Graph\Node;
use Goat\Mapper\Query\Graph\PropertyNode;
use Goat\Mapper\Query\Graph\RootNode;
use Goat\Query\ExpressionColumn;

class MatchVisitor extends AbstractVisitor
{
    /**
     * {@inheritdoc}
     */
    public function onRootNode(RootNode $node, EntityQuery $context): void
    {
        if ($node->shouldMatch()) {
            $this->doApplyNodeConditions($node, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPropertyNode(PropertyNode $node, Node $parent, EntityQuery $context): void
    {
        if ($node->shouldAnyChildThatMatch()) {
            // JOIN at least.
        }
        if ($node->shouldMatch()) {
            $this->doApplyNodeConditions($node, $context);
        }
    }

    private function doApplyNodeConditions(Node $node, EntityQuery $context, ?string $propertyName = null): void
    {
        if (!$node->shouldMatch()) {
            return;
        }

        $query = $context->getQuery();

        $targetTableAlias = $node->getAlias();
        $targetDefinition = $context->getDefinitionRegistry()->getDefinition($node->getClassName());

        foreach ($node->getConditions() as $propertyName => $expressions) {
            if ($columnName = $targetDefinition->getColumnName($propertyName)) {
                foreach ($expressions as $expression) {
                    $query->condition(
                        ExpressionColumn::create($columnName, $targetTableAlias),
                        $expression
                    );
                }
            } else {
                // Do NOT prefix column names with the table alias if the column
                // is unknown to the entity definition: this way, the user can
                // write arbitrary expressions in the query.
                foreach ($expressions as $expression) {
                    $query->condition($propertyName, $expression);
                }
            }
        }
    }
}
