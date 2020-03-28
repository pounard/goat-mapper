<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

class Column
{
    private string $name;
    private string $type;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get SQL type
     */
    public function getType(): string
    {
        return $this->type;
    }
}
