<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/* final */ class WithToOneInTargetRelation
{
    /** @var UuidInterface */
    private $id;

    /** @var null|WithToOneInSourceRelation */
    private $relatedEntity;

    public static function toDefinitionArray(): array
    {
        return [
            'table' => 'to_one_in_target',
            'primary_key' => [
                'id' => 'uuid',
            ],
            'columns' => [
                'id' => 'id',
            ],
            'relations' => [
                [
                    'class_name' => WithToOneInSourceRelation::class,
                    'property_name' => 'relatedEntity',
                    'table' => 'to_one_in_source',
                    'mode' => 'one_to_one',
                    'key_in' => 'target',
                    'target_key' => [
                        'target_id' => 'uuid',
                    ],
                ],
            ],
        ];
    }

    public static function toTableSchema(): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE to_one_in_target (
    id UUID NOT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE to_one_in_target (
    id VARCHAR(36) NOT NULL,
    target_id VARCHAR(36) DEFAULT NULL,
    PRIMARY KEY (id)
)
SQL
        ];
    }

    public function getId(): UuidInterface
    {
        return $this->id ?? ($this->id = Uuid::uuid4());
    }

    public function getRelatedEntity(): ?WithToOneInSourceRelation
    {
        return $this->relatedEntity;
    }
}
