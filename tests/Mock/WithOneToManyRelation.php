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
    private ?int $serial = null;
    private ?string $value = null;
    /** @var WithManyToOneRelation[] */
    private ?Collection $relatedCollection = null;
    /** @var WithManyToOneRelation[] */
    private ?Collection $relatedCollectionUsingSerial = null;

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
        $relation = $builder->addOneToManyRelation('relatedCollectionUsingSerial', WithManyToOneRelation::class);
        $relation->setTargetKey(['related_entity_serial' => 'int']);
        $relation->setSourceKeyIfNotPrimaryKey(['serial' => 'int']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.with_one_to_many (
    id UUID NOT NULL,
    serial SERIAL NOT NULL,
    value TEXT DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE(serial)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE with_one_to_many (
    id VARCHAR(36) NOT NULL,
    serial INTEGER NOT NULL AUTO_INCREMENT,
    value TEXT DEFAULT NULL
    PRIMARY KEY (id),
    UNIQUE(serial)
)
SQL
        ];
    }

    public function getId(): UuidInterface
    {
        return $this->id ?? ($this->id = Uuid::uuid4());
    }

    public function getSerial(): ?int
    {
        return $this->serial;
    }

    public function getRelatedCollection(): ?iterable
    {
        return $this->relatedCollection;
    }
}
