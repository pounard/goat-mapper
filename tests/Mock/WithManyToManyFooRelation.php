<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WithManyToManyFooRelation implements StaticEntityDefinition
{
    private ?UuidInterface $id = null;
    private ?int $serial = null;
    /** @var WithManyToManyBarRelation[] */
    private ?iterable $relatedCollection = null;

    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('with_many_to_many_foo');
        $builder->addProperty('id');
        $builder->addProperty('serial');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addManyToManyRelation('relatedCollection', WithManyToManyBarRelation::class);
        $relation->setMappingTable('bar_to_foo');
        $relation->setMappingSourceKey(['foo_id' => 'int']);
        $relation->setSourceKeyIfNotPrimaryKey(['serial' => 'int']);
        $relation->setMappingTargetKey(['bar_id' => 'uuid']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => [
                <<<SQL
CREATE TABLE {$schema}.with_many_to_many_foo (
    id UUID NOT NULL,
    serial SERIAL NOT NULL,
    PRIMARY KEY (id),
    UNIQUE(serial)
)
SQL
                ,
                <<<SQL
CREATE TABLE {$schema}.bar_to_foo (
    foo_id INT NOT NULL,
    bar_id UUID NOT NULL,
    PRIMARY KEY (foo_id, bar_id),
    FOREIGN KEY (foo_id)
        REFERENCES {$schema}.with_many_to_many_foo (serial)
        ON DELETE CASCADE,
    FOREIGN KEY (bar_id)
        REFERENCES {$schema}.with_many_to_many_bar (id)
        ON DELETE CASCADE
)
SQL
            ],
            'mysql' => [
                <<<SQL
CREATE TABLE with_many_to_many_foo (
    id VARCHAR(36) NOT NULL,
    serial INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (id),
    UNIQUE(serial)
)
SQL
                ,
                <<<SQL
CREATE TABLE bar_to_foo (
    foo_id INTEGER NOT NULL,
    bar_id VARCHAR(36) NOT NULL,
    PRIMARY KEY (foo_id, bar_id),
    FOREIGN KEY (foo_id)
        REFERENCES with_many_to_many_foo (serial)
        ON DELETE CASCADE,
    FOREIGN KEY (bar_id)
        REFERENCES with_many_to_many_bar (id)
        ON DELETE CASCADE
)
SQL
            ],
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
