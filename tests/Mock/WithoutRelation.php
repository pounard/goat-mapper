<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WithoutRelation implements StaticEntityDefinition
{
    /** @var UuidInterface */
    private $id;

    /** @var null|string */
    private $value;

    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('without_relation');
        $builder->addProperty('id');
        $builder->addProperty('value');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.without_relation (
    id UUID NOT NULL,
    value TEXT DEFAULT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE without_relation (
    id VARCHAR(36) NOT NULL,
    value TEXT DEFAULT NULL,
    PRIMARY KEY (id)
)
SQL
        ];
    }

    public function getId(): UuidInterface
    {
        return $this->id ?? ($this->id = Uuid::uuid4());
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
