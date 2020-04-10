<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\UuidInterface;

class Address implements StaticEntityDefinition
{
    private UuidInterface $id;
    private string $type = 'livraison';
    private UuidInterface $clientId;
    private Client $client;
    private ?string $line1 = null;
    private ?string $line2 = null;
    private ?string $locality = null;
    private ?string $zipCode = null;
    private ?string $countryCode = null;
    private ?Country $country;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getClientId(): UuidInterface
    {
        return $this->clientId;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    /**
     * {@inheritdoc}
     */
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('client_address');
        $builder->addProperty('id');
        $builder->addProperty('clientId', 'client_id');
        $builder->addProperty('type');
        $builder->addProperty('line1');
        $builder->addProperty('line2');
        $builder->addProperty('locality');
        $builder->addProperty('zipCode', 'zipcode');
        $builder->addProperty('countryCode', 'country');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addAnyToOneRelation('client', Client::class);
        $relation->setSourceKey(['clientId' => 'uuid']);
        $relation = $builder->addAnyToOneRelation('country', Country::class);
        $relation->setSourceKey(['countryCode' => 'string']);
        $relation->setTargetKeyIfNotPrimaryKey(['code' => 'string']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.client_address (
    id UUID NOT NULL,
    client_id UUID NOT NULL,
    type VARCHAR(255) NOT NULL,
    line1 VARCHAR(64) DEFAULT NULL,
    line2 VARCHAR(64) DEFAULT NULL,
    locality VARCHAR(64) DEFAULT NULL,
    zipcode VARCHAR(16) DEFAULT NULL,
    country VARCHAR(5) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE (client_id, type),
    FOREIGN KEY (client_id)
        REFERENCES {$schema}.client (id)
        ON DELETE CASCADE
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE client_address (
    id VARCHAR(36) NOT NULL,
    client_id UUID NOT NULL,
    type VARCHAR(255) NOT NULL,
    line1 VARCHAR(64) DEFAULT NULL,
    line2 VARCHAR(64) DEFAULT NULL,
    locality VARCHAR(64) DEFAULT NULL,
    zipcode VARCHAR(16) DEFAULT NULL,
    country VARCHAR(5) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE (client_id, type),
    FOREIGN KEY (client_id)
        REFERENCES client (id)
        ON DELETE CASCADE
)
SQL
        ];
    }
}
