<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;

class Country implements StaticEntityDefinition
{
    private string $code;
    private string $title;

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('country_list', 'public');
        $builder->addProperty('code');
        $builder->addProperty('title', 'name');
        $builder->setPrimaryKey([
            'code' => 'string',
        ]);
    }

    public static function toTableSchema(): array
    {
        return [
            'pgsql' => <<<SQL
CREATE TABLE country_list (
    code VARCHAR(10) NOT NULL,
    title TEXT NOT NULL,
    PRIMARY KEY (code)
)
SQL
            ,
            'mysql' => <<<SQL
CREATE TABLE country_list (
    code VARCHAR(10) NOT NULL,
    title TEXT NOT NULL,
    PRIMARY KEY (code)
)
SQL
        ];
    }
}
