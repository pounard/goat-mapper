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
    private ?UuidInterface $advisorId = null;
    private ?Advisor $personalAdvisor;

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

    public function getPersonalAdvisor(): ?Advisor
    {
        return $this->personalAdvisor;
    }

    /**
     * {@inheritdoc}
     */
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('client');
        $builder->addProperty('id');
        $builder->addProperty('firstname');
        $builder->addProperty('lastname');
        $builder->addProperty('advisorId', 'advisor_id');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);

        $relation = $builder->addAnyToOneRelation('personalAdvisor', Advisor::class);
        $relation->setSourceKey(['advisor_id' => 'uuid']);

        $relation = $builder->addOneToManyRelation('addresses', Address::class);
        $relation->setTargetKey(['client_id' => 'uuid']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.client (
    id UUID NOT NULL,
    firstname VARCHAR(512) NOT NULL,
    lastname VARCHAR(512) NOT NULL,
    advisor_id UUID DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (advisor_id)
        REFERENCES {$schema}.salesman (id)
        ON DELETE SET NULL
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE client (
    id VARCHAR(36) NOT NULL,
    firstname VARCHAR(512) NOT NULL,
    lastname VARCHAR(512) NOT NULL,
    advisor_id VARCHAR(36) DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (advisor_id)
        REFERENCES salesman (id)
        ON DELETE SET NULL
SQL
        ];
    }
}
