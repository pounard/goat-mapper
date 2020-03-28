<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph;

final class RootNode extends Node
{
    private ?Source $source = null;

    public function withSource(Source $source): void
    {
        $this->source = $source;
    }

    public function getSource(): ?Source
    {
        return $this->source;
    }
}
