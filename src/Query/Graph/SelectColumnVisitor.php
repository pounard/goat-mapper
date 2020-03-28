<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph;

use Goat\Mapper\Error\QueryError;
use Goat\Query\ExpressionColumn;

class SelectColumnVisitor extends Visitor
{
    /**
     * {@inheritdoc}
     */
    public function onRootNode(RootNode $node, EntityQuery $context): void
    {
        if ($node->shouldLoad()) {
            $this->doAddNodeColumns($node, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPropertyNode(PropertyNode $node, Node $parent, EntityQuery $context): void
    {
        if ($node->shouldLoad()) {
            $this->doAddNodeColumns($node, $context, $node->getPath());
        }
    }

    /**
     * Add repository columns to the given select query.
     */
    private function doAddNodeColumns(Node $node, EntityQuery $context, ?string $propertyName = null): void
    {
        $className = $node->getClassName();
        $query = $context->getQuery();
        $tableAlias = $node->getAlias();

        $targetDefinition = $context->getDefinitionRegistry()->getDefinition($className);

        // Add related object columns to SELECT clause. They will be prefixed
        // using the property name and a dot, which will allow the custom
        // hydrator to handled nested objects hydration.
        // @todo make the nested hydrator really lazy using a proxy object.
        if ($columns = $targetDefinition->getColumnMap()) {
            foreach ($columns as $targetPropertyName => $columName) {
                $query->column(
                    ExpressionColumn::create($columName, $tableAlias),
                    $propertyName ? $propertyName.'.'.$targetPropertyName : $targetPropertyName
                );
            }
        } else {
            throw new QueryError("Cannot eargerly fetch a related entity whose properties are not defined.");
        }

        // @todo Not sure this should be restored...
        // $select->setOption('types', $this->defineSelectColumnsTypes());
        // @todo prepare hydrator to hydrate nested.
        // @todo handle potential name conflicts.
    }
}
