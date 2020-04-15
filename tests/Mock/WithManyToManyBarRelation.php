<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WithManyToManyBarRelation implements StaticEntityDefinition
{
    private ?UuidInterface $id = null;
    /** @var WithManyToManyFooRelation[] */
    private ?iterable $relatedCollection = null;

    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('with_many_to_many_bar');
        $builder->addProperty('id');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addManyToManyRelation('relatedCollection', WithManyToManyFooRelation::class);
        $relation->setMappingTable('bar_to_foo');
        $relation->setMappingSourceKey(['bar_id' => 'uuid']);
        $relation->setMappingTargetKey(['foo_id' => 'int']);
        $relation->setTargetKeyIfNotPrimaryKey(['serial' => 'int']);
    }

    public static function toDefinitionArray(): array
    {
        throw new \Exception("Implement me.");

        return [
            'table' => 'with_many_to_many_bar',
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
                    'mode' => 'many_to_many',
                    'key_in' => 'mapping',
                    'target_key' => [
                        'id' => 'uuid',
                    ],
                ],
            ],
        ];
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.with_many_to_many_bar (
    id UUID NOT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE with_many_to_many_bar (
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

    public function getRelatedCollection(): iterable
    {
        return $this->relatedCollection ?? [];
    }
}
