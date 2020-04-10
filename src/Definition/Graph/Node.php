<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

interface Node
{
    /** @return Node[] */
    public function getChildren(): iterable;
}
