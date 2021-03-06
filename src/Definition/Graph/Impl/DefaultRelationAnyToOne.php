<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationAnyToOne;
use Goat\Mapper\Error\ConfigurationError;

final class DefaultRelationAnyToOne extends AbstractRelation implements RelationAnyToOne
{
    private bool $doEagerLoad = true;

    public function __construct(Entity $entity, string $name, string $className, ?int $mode = Relation::MODE_MANY_TO_ONE)
    {
        if (Relation::MODE_MANY_TO_ONE !== $mode && Relation::MODE_ONE_TO_ONE !== $mode) {
            throw new ConfigurationError(\sprintf("Mode must be many to one or one to one.", __CLASS__));
        }

        parent::__construct($entity, $name, $className, $mode);
    }

    /**
     * Toggle eager load per default.
     */
    public function toggleEagerLoad(bool $toggle): void
    {
        $this->doEagerLoad = $toggle;
    }

    /**
     * {@inheritdoc}
     */
    public function doEagerLoad(): bool
    {
        return $this->doEagerLoad;
    }

    /**
     * {@inheritdoc}
     */
    protected function findDefaultTargetKey(): Key
    {
        return $this->getEntity()->getPrimaryKey();
    }
}
