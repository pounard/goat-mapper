<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\EntityHydrator;

use Goat\Mapper\Query\Relation\RelationFetcher;

final class EntityHydratorContext
{
    /** @var string */
    public $className;

    /** @var string[] */
    public $lazyPropertyNames = [];

    /** @var null|RelationFetcher */
    public $relationFetcher;

    public function __construct(string $className)
    {
        $this->className = $className;
    }
}
