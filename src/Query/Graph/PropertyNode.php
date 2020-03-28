<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph;

class PropertyNode extends Node
{
    /** @var string */
    protected $propertyName;

    /**
     * @param string $path
     *   Property full path relative to root query context.
     */
    public function __construct(string $propertyName, string $className, string $path)
    {
        parent::__construct($className);

        $this->propertyName = $propertyName;
        $this->path = $path;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $propertyName, string $className): PropertyNode
    {
        return $this->children[$propertyName] = new PropertyNode(
            $propertyName,
            $className,
            $this->path . '.' . $propertyName
        );
    }
}
