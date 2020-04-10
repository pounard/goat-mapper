<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

class Relation extends Property
{
    const MODE_ONE_TO_ONE = 1;
    const MODE_ONE_TO_MANY = 2;
    const MODE_MANY_TO_ONE = 3;
    const MODE_MANY_TO_MANY = 4;

    private int $mode;
    private Entity $entity;

    public function __construct(Entity $entity, string $name, int $mode)
    {
        if ($mode < 1 || 4 < $mode) {
            throw new \InvalidArgumentException(\sprintf("Mode must be one of the %s::MODE_* constants.", __CLASS__));
        }

        parent::__construct($name);

        $this->entity = $entity;
        $this->mode = $mode;
    }

    /**
     * Will the other side be a collection?
     */
    public function isMultiple(): bool
    {
        return $this->mode === self::MODE_MANY_TO_MANY || $this->mode === self::MODE_ONE_TO_MANY;
    }

    /**
     * Get relation mode.
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * Get related entity.
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): iterable
    {
        return [$this->entity];
    }
}

/* 
    /** One to one (remote key is unique) * /
    const MODE_ONE_TO_ONE = 1;

    /** One to many (remote key is not unique) * /
    const MODE_ONE_TO_MANY = 2;

    /** Many to one (local key is unique) * /
    const MODE_MANY_TO_ONE = 3;

    /** Many to many (local key is not unique) * /
    const MODE_MANY_TO_MANY = 4;

    /** Target entity primary key is in source table * /
    const KEY_IN_SOURCE = 1;

    /** Target entity primary key is in target table * /
    const KEY_IN_TARGET = 2;

    /** Target entity primary key is in mapping table * /
    const KEY_IN_MAPPING = 3;

    private string $className;
    private string $propertyName;
    private int $mode;
    private int $keyIn;
    private Table $targetTable;
    private Table $sourceTable;
    private Key $targetKey;
    private Key $sourceKey;

    public function __construct(
        string $propertyName,
        string $className,
        int $mode,
        Table $targetTable,
        Table $sourceTable,
        Key $targetKey,
        Key $sourceKey,
        ?int $keyIn = null
    ) {
        if ($mode < 1 || 4 < $mode) {
            throw new \InvalidArgumentException(\sprintf("Mode must be one of the %s::MODE_* constants.", __CLASS__));
        }
        if ($mode === self::MODE_MANY_TO_MANY) {
            throw new \InvalidArgumentException("Many to many is not supported yet as it needs a mapping table.");
        }
        if (!$sourceKey->isCompatible($targetKey)) {
            throw new \InvalidArgumentException(\sprintf("Target key and source key must be compatible.", __CLASS__));
        }

        if (null === $keyIn) {
            switch ($mode) {

                case self::MODE_MANY_TO_MANY:
                    $keyIn = self::KEY_IN_MAPPING;
                    break;

                case self::MODE_MANY_TO_ONE:
                    $keyIn = self::KEY_IN_TARGET;
                    break;

                case self::MODE_ONE_TO_MANY:
                    $keyIn = self::KEY_IN_SOURCE;
                    break;

                case self::MODE_ONE_TO_ONE:
                    // This seems like a sensible default, but you have 50%
                    // chances to find the key in the target table as well.
                    // It's an arbitrary decision that this is the default.
                    $keyIn = self::KEY_IN_SOURCE;
                    break;
            }
        } else if ($keyIn < 1 || 3 < $keyIn) {
            throw new \InvalidArgumentException(\sprintf("\$keyIn must be one of the %s::* constants.", __CLASS__));
        } else if ((self::MODE_MANY_TO_MANY === $mode || self::MODE_ONE_TO_MANY === $mode) && self::KEY_IN_SOURCE === $keyIn) {
            throw new \InvalidArgumentException(\sprintf("Relation key cannot be in source for any to many relations.", __CLASS__));
        } else if ((self::MODE_MANY_TO_MANY === $mode || self::MODE_MANY_TO_ONE === $mode) && self::KEY_IN_TARGET === $keyIn) {
            throw new \InvalidArgumentException(\sprintf("Relation key cannot be in target for many to any relations.", __CLASS__));
        }

        $this->className = $className;
        $this->keyIn = $keyIn;
        $this->mode = $mode;
        $this->propertyName = $propertyName;
        $this->sourceKey = $sourceKey;
        $this->sourceTable = $sourceTable;
        $this->targetKey = $targetKey;
        $this->targetTable = $targetTable;
    }
 */
