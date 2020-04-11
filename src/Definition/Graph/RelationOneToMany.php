<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

/**
 * For one to many relationships, we consider that the foreign key is always
 * in the target table. Many to many are handled differently.
 */
interface RelationOneToMany extends Relation
{
}
