<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\UuidInterface;

class Client implements StaticEntityDefinition
{
    private UuidInterface $id;
    private string $firstname;
    private string $lastname;
    private ?iterable $addresses;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    /** @return Address[] */
    public function getAddresses(): iterable
    {
        return $this->addresses ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('client', 'public');
        $builder->addProperty('id');
        $builder->addProperty('firstname');
        $builder->addProperty('lastname');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addOneToManyRelation('addresses', Address::class);
        $relation->keyIsInTargetTable();
        $relation->setTargetTableName('client_address', 'public');
        $relation->setTargetKey(['clientId' => 'uuid']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.client (
    id UUID NOT NULL,
    firstname VARCHAR(512) NOT NULL,
    lastname VARCHAR(512) NOT NULL,
    PRIMARY KEY (id)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE client (
    id VARCHAR(36) NOT NULL,
    firstname VARCHAR(512) NOT NULL,
    lastname VARCHAR(512) NOT NULL,
    PRIMARY KEY (id)
)
SQL
        ];
    }
}
