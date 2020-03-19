<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

/* final */ class WithoutRelation
{
    /** @var UuidInterface */
    private $id;

    /** @var null|string */
    private $value;

    public static function toDefinitionArray(): array
    {
        return [
            'table' => 'without_relation',
            'primary_key' => [
                'id' => 'uuid',
            ],
            'columns' => [
                'id' => 'id',
                'value' => 'value',
            ],
        ];
    }

    public function getId(): UuidInterface
    {
        return $this->id ?? ($this->id = Uuid::uuid4());
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
