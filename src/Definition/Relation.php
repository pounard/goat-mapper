<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

/**
 * @todo create JoinTable object and make this extend it or compose with it
 */
class Relation
{
    /** One to one (remote key is unique) */
    const MODE_ONE_TO_ONE = 1;

    /** One to many (remote key is not unique) */
    const MODE_ONE_TO_MANY = 2;

    /** Many to one (local key is unique) */
    const MODE_MANY_TO_ONE = 3;

    /** Many to many (local key is not unique) */
    const MODE_MANY_TO_MANY = 4;

    /** @var string */
    private $className;

    /** @var string */
    private $propertyName;

    /** @var int */
    private $mode;

    /** @var Table */
    private $table;

    /** @var Key */
    private $key;

    public function __construct(string $propertyName, string $className, int $mode, Table $table, Key $key)
    {
        if ($mode < 1 || 4 < $mode) {
            throw new \InvalidArgumentException(\sprintf("Mode must be one of the %s::MODE_* constants.", __CLASS__));
        }
        if ($mode === self::MODE_MANY_TO_MANY) {
            throw new \InvalidArgumentException("Many to many is not supported yet as it needs a mapping table.");
        }

        $this->className = $className;
        $this->key = $key;
        $this->mode = $mode;
        $this->propertyName = $propertyName;
        $this->table = $table;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /* @todo Not sure this shoud exist on this class, it makes it aware of its context */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getKey(): Key
    {
        return $this->key;
    }
}
