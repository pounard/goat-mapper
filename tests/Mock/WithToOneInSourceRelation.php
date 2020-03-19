<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/* final */ class WithToOneInSourceRelation
{
    /** @var UuidInterface */
    private $id;

    /** @var null|WithoutRelation */
    private $relatedEntity;

    public static function toDefinitionArray(): array
    {
        return [
            'table' => 'to_one_in_source',
            'primary_key' => [
                'id' => 'uuid',
            ],
            'columns' => [
                'id' => 'id',
                'targetId' => 'target_id',
            ],
            'relations' => [
                [
                    'class_name' => WithoutRelation::class,
                    'property_name' => 'relatedEntity',
                    'table' => 'without_relation',
                    'mode' => 'one_to_one',
                    'key_in' => 'source',
                    'target_key' => [
                        'id' => 'uuid',
                    ],
                    'source_key' => [
                        'target_id' => 'uuid',
                    ],
                ],
            ],
        ];
    }

    public function getId(): UuidInterface
    {
        return $this->id ?? ($this->id = Uuid::uuid4());
    }

    public function getRelatedEntity(): ?WithoutRelation
    {
        return $this->relatedEntity;
    }
}
