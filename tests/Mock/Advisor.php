<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\UuidInterface;

class Advisor implements StaticEntityDefinition
{
    private UuidInterface $id;
    private int $serial;
    private string $firstname;
    private string $lastname;
    private ?string $phoneNumber;
    private ?UuidInterface $serviceId;
    private ?Service $service;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getSerialId(): int
    {
        return $this->serial;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getServiceId(): ?UuidInterface
    {
        return $this->serviceId;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('salesman');
        $builder->addProperty('id');
        $builder->addProperty('serial');
        $builder->addProperty('firstname');
        $builder->addProperty('lastname');
        $builder->addProperty('phoneNumber', 'phone_number');
        $builder->addProperty('serviceId', 'service_id');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);

        $relation = $builder->addAnyToOneRelation('service', Service::class);
        $relation->setSourceKey(['service_id' => 'uuid']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.salesman (
    id UUID NOT NULL,
    serial BIGSERIAL NOT NULL,
    firstname VARCHAR(512) NOT NULL,
    lastname VARCHAR(512) NOT NULL,
    phone_number VARCHAR(20) DEFAULT NULL,
    service_id UUID DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (service_id)
        REFERENCES {$schema}.service (id)
        ON DELETE SET NULL
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE salesman (
    id VARCHAR(36) NOT NULL,
    id INTEGER NOT NULL AUTO_INCREMENT, 
    firstname VARCHAR(512) NOT NULL,
    lastname VARCHAR(512) NOT NULL,
    phone_number VARCHAR(20) DEFAULT NULL,
    service_id VARCHAR(36) DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (service_id)
        REFERENCES service (id)
        ON DELETE SET NULL
)
SQL
        ];
    }
}
