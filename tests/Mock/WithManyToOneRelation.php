<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WithManyToOneRelation implements StaticEntityDefinition
{
    private UuidInterface $id;
    private UuidInterface $relatedEntityId;
    private ?WithOneToManyRelation $relatedEntity = null;

    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('with_many_to_one');
        $builder->addProperty('id');
        $builder->addProperty('relatedEntityId', 'related_entity_id');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addAnyToOneRelation('relatedEntity', WithOneToManyRelation::class);
        $relation->setSourceKey(['related_entity_id' => 'uuid']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.with_many_to_one (
    id UUID NOT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE with_many_to_one (
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
        return $this->relatedCollection;
    }
}
