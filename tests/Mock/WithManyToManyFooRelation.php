<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WithManyToManyFooRelation implements StaticEntityDefinition
{
    /** @var UuidInterface */
    private $id;

    /** @var WithManyToManyBarRelation[] */
    private $relation;

    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('with_many_to_many_a');
        $builder->addProperty('id');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addManyToManyRelation('relation', WithManyToManyBarRelation::class);
        $relation->setTargetTableName('a_to_b');
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.to_many_in_mapping (
    id UUID NOT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE to_many_in_mapping (
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
