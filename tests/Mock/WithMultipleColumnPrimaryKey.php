<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

/* final */ class WithMultipleColumnPrimaryKey
{
    /** @var int */
    private $pkey1;

    /** @var string */
    private $pkey2;

    /** @var null|string */
    private $value;

    public static function toDefinitionArray(): array
    {
        return [
            'table' => 'with_multiple_key',
            'primary_key' => [
                'pkey1' => 'int',
                'pkey2' => 'string',
            ],
            'columns' => [
                'pkey1' => 'pkey1',
                'pkey2' => 'pkey2',
                'value' => 'value',
            ],
        ];
    }

    public static function toTableSchema(): array
    {
        return [
            'default' => <<<SQL
CREATE TABLE with_multiple_key (
    pkey1 INT NOT NULL,
    pkey2 VARCHAR(128) NOT NULL,
    value TEXT DEFAULT NULL
    PRIMARY KEY (pkey1, pkey2)
)
SQL
        ];
    }

    public function getPkey1(): int
    {
        return $this->pkey1;
    }

    public function getPkey2(): string
    {
        return $this->pkey2;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
