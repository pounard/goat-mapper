<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
use Ramsey\Uuid\UuidInterface;

class Product implements StaticEntityDefinition
{
    private UuidInterface $id;
    private string $reference;
    private string $title;
    private int $price;
    private ?iterable $tags;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    /** @return ProductTag[] */
    public function getTags(): iterable
    {
        return $this->tags ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('product', 'public');
        $builder->addProperty('id');
        $builder->addProperty('reference');
        $builder->addProperty('title');
        $builder->addProperty('price');
        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);
        $relation = $builder->addManyToManyRelation('tags', ProductTag::class);
        $relation->setTargetTableName('tag', 'public');
        $relation->setSourceKey(['product_id' => 'uuid']);
        $relation->setTargetKey(['tag_id' => 'int']);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE {$schema}.product (
    id UUID NOT NULL,
    reference VARCHAR(64) NOT NULL,
    title VARCHAR(512) NOT NULL,
    price INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (reference)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE product (
    id VARCHAR(36) NOT NULL,
    reference VARCHAR(64) NOT NULL,
    title VARCHAR(512) NOT NULL,
    price INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (reference)
)
SQL
        ];
    }
}
