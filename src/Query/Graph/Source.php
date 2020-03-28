<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph;

use Goat\Mapper\Definition\Identifier;

final class Source
{
    private string $className;
    private string $propertyName;
    /** @var Identifier[] */
    private iterable $identifiers;

    public function __construct(string $className, string $propertyName, iterable $identifiers)
    {
        $this->className = $className;
        $this->propertyName = $propertyName;
        $this->identifiers = $identifiers;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /** @return Identifier */
    public function getIdentifiers(): iterable
    {
        return $this->identifiers;
    }
}
