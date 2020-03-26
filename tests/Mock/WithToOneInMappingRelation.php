<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/* final */ class WithToOneInMappingRelation
{
    /** @var UuidInterface */
    private $id;

    /** @var null|WithoutRelation */
    private $relatedEntity;

    public static function toDefinitionArray(): array
    {
        return [
            'table' => 'to_one_in_mapping',
            'primary_key' => [
                'id' => 'uuid',
            ],
            'columns' => [
                'id' => 'id',
            ],
            'relations' => [
                [
                    'class_name' => WithoutRelation::class,
                    'property_name' => 'relatedEntity',
                    'table' => 'without_relation',
                    'mode' => 'one_to_one',
                    'key_in' => 'mapping',
                    'target_key' => [
                        'id' => 'uuid',
                    ],
                ],
            ],
        ];
    }

    public static function toTableSchema(): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE to_one_in_mapping (
    id UUID NOT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE to_one_in_mapping (
    id VARCHAR(36) NOT NULL,
    PRIMARY KEY (id)
)
SQL
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
