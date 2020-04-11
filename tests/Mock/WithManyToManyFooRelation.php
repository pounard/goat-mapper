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
        $builder->setTableName('with_many_to_many_foo');
        $builder->addProperty('id');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addManyToManyRelation('relation', WithManyToManyBarRelation::class);
        $relation->setTargetTableName('bar_to_foo');
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.with_many_to_many_foo (
    id UUID NOT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE with_many_to_many_foo (
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
