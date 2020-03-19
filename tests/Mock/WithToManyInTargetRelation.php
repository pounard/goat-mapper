<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/* final */ class WithToManyInTargetRelation
{
    /** @var UuidInterface */
    private $id;

    /** @var WithoutRelation[] */
    private $relatedCollection;

    public static function toDefinitionArray(): array
    {
        return [
            'table' => 'to_many_in_target',
            'primary_key' => [
                'id' => 'uuid',
            ],
            'columns' => [
                'id' => 'id',
            ],
            'relations' => [
                [
                    'class_name' => WithoutRelation::class,
                    'property_name' => 'relatedCollection',
                    'table' => 'without_relation',
                    'mode' => 'one_to_many',
                    'key_in' => 'target',
                    'target_key' => [
                        'id' => 'uuid',
                    ],
                ],
            ],
        ];
    }

    public function getId(): UuidInterface
    {
        return $this->id ?? ($this->id = Uuid::uuid4());
    }

    public function getRelatedCollection(): iterable
    {
        return $this->relatedCollection;
    }
}
