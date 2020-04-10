<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;

class ProductTag implements StaticEntityDefinition
{
    private int $id;
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('tag');
        $builder->addProperty('name');
        $builder->setPrimaryKey([
            'name' => 'string',
        ]);
    }

    public static function toTableSchema(string $schema): array
    {
        return [
            'pgsql' => [
                <<<SQL
CREATE TABLE {$schema}.tag (
    id BIGSERIAL,
    name VARCHAR(64) NOT NULL,
    PRIMARY KEY (id)
);
SQL
                ,
                <<<SQL
CREATE TABLE {$schema}.product_tag (
    tag_id BIGINT NOT NULL,
    product_id UUID NOT NULL,
    PRIMARY KEY (tag_id, product_id),
    FOREIGN KEY (tag_id)
        REFERENCES {$schema}.tag (id)
        ON DELETE CASCADE,
    FOREIGN KEY (product_id)
        REFERENCES {$schema}.product (id)
        ON DELETE CASCADE
);
SQL
            ],
            'mysql' => [
                <<<SQL
CREATE TABLE tag (
    id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(64) NOT NULL,
    PRIMARY KEY (id)
);
SQL
                ,
                <<<SQL
CREATE TABLE product_tag (
    tag_id BIGINT NOT NULL,
    product_id UUID NOT NULL,
    PRIMARY KEY (tag_id, product_id),
    FOREIGN KEY (tag_id)
        REFERENCES tag (id)
        ON DELETE CASCADE,
    FOREIGN KEY (product_id)
        REFERENCES product (id)
        ON DELETE CASCADE
);
SQL
            ],
        ];
    }
}
