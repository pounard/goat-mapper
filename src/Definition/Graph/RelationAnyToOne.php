<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

/**
 * For any to one relationships, we consider that the foreign key is always
 * in the source table.
 */
interface RelationAnyToOne extends Relation
{
}
