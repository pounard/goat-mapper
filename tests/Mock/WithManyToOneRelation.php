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

    private ?int $relatedEntitySerial = null;
    private ?WithOneToManyRelation $relatedEntityUsingSerial = null;

    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('with_many_to_one');
        $builder->addProperty('id');
        $builder->addProperty('relatedEntityId', 'related_entity_id');
        $builder->addProperty('relatedEntitySerial', 'related_entity_serial');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addAnyToOneRelation('relatedEntity', WithOneToManyRelation::class);
        $relation->setSourceKey(['related_entity_id' => 'uuid']);
        $relation = $builder->addAnyToOneRelation('relatedEntityUsingSerial', WithOneToManyRelation::class);
        $relation->setSourceKey(['related_entity_serial' => 'int']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.with_many_to_one (
    id UUID NOT NULL,
    related_entity_id UUID DEFAULT NULL,
    related_entity_serial INT DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (related_entity_id)
        REFERENCES {$schema}.with_one_to_many (id)
        ON DELETE SET NULL,
    FOREIGN KEY (related_entity_serial)
        REFERENCES {$schema}.with_one_to_many (serial)
        ON DELETE SET NULL
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE with_many_to_one (
    id VARCHAR(36) NOT NULL,
    related_entity_id VARCHAR(36) DEFAULT NULL,
    related_entity_serial INT DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (related_entity_id)
        REFERENCES with_one_to_many (id)
        ON DELETE SET NULL,
    FOREIGN KEY (related_entity_serial)
        REFERENCES with_one_to_many (serial)
        ON DELETE SET NULL
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
