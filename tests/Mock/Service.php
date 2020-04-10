<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\UuidInterface;

class Service implements StaticEntityDefinition
{
    private UuidInterface $id;
    private string $name;
    /** @var Advisor[] */
    private ?iterable $employees;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return Advisor[] */
    public function getEmployees(): iterable
    {
        return $this->employees ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('service');
        $builder->addProperty('id');
        $builder->addProperty('name');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addOneToManyRelation('employees', Advisor::class);
        $relation->setTargetKey(['serviceId' => 'uuid']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.service (
    id UUID NOT NULL,
    name TEXT NOT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE service (
    id VARCHAR(36) NOT NULL,
    name TEXT NOT NULL,
    PRIMARY KEY (id)
)
SQL
        ];
    }
}
