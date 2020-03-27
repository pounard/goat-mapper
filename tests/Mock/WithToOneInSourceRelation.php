<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/* final */ class WithToOneInSourceRelation implements StaticEntityDefinition
{
    /** @var UuidInterface */
    private $id;

    /** @var null|WithToOneInTargetRelation */
    private $relatedEntity;

    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('to_one_in_source', 'public');
        $builder->addProperty('id');
        $builder->addProperty('targetId', 'target_id');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addOneToOneRelation('relatedEntity', WithToOneInTargetRelation::class);
        $relation->setTargetTableName('to_one_in_target', 'public');
        $relation->keyIsInSourceTable();
        $relation->setTargetKey([
            'id' => 'uuid',
        ]);
        $relation->setSourceKey([
            'target_id' => 'uuid',
        ]);
    }

    /*
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
                    'class_name' => WithToOneInTargetRelation::class,
                    'property_name' => 'relatedEntity',
                    'table' => 'to_one_in_target',
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
     */

    public static function toTableSchema(): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE to_one_in_source (
    id UUID NOT NULL,
    target_id UUID DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (target_id)
        REFERENCES to_one_in_target (id)
        ON DELETE SET NULL
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE to_one_in_source (
    id VARCHAR(36) NOT NULL,
    target_id VARCHAR(36) DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (target_id)
        REFERENCES to_one_in_target (id)
        ON DELETE SET NULL
)
SQL
        ];
    }

    public function getId(): UuidInterface
    {
        return $this->id ?? ($this->id = Uuid::uuid4());
    }

    public function getRelatedEntity(): ?WithToOneInTargetRelation
    {
        return $this->relatedEntity;
    }
}
