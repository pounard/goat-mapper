<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Goat\Mapper\Hydration\Collection\Collection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WithOneToManyRelation implements StaticEntityDefinition
{
    private UuidInterface $id;
    private ?string $value = null;
    /** @var WithManyToOneRelation[] */
    private ?Collection $relatedCollection = null;

    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('with_one_to_many');
        $builder->addProperty('id');
        $builder->addProperty('value');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addOneToManyRelation('relatedCollection', WithManyToOneRelation::class);
        $relation->setTargetKey(['related_entity_id' => 'uuid']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.with_one_to_many (
    id UUID NOT NULL,
    value TEXT DEFAULT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE with_one_to_many (
    id VARCHAR(36) NOT NULL,
    value TEXT DEFAULT NULL
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
