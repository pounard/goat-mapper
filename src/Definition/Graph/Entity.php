<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Table;

/**
 * Represent an entity.
 *
 * Repository is a transcient concept for us, it may, or may not exists, the
 * entity relation graph is sufficient for builder SQL queries and hydrate
 * classes.
 *
 * Repository is to be implemented only in case the using application needs
 * to create typed interfaces, and decouple this component from its business.
 */
interface Entity extends Node
{
    /**
     * Get entity PHP class name.
     */
    public function getClassName(): string;

    /**
     * Get entity primary table.
     */
    public function getTable(): Table;

    /**
     * Get entity primary if any.
     */
    public function getPrimaryKey(): ?PrimaryKey;

    /**
     * Get a single relation for given property or class name.
     *
     * @return array<string,Relation>
     */
    public function getRelation(string $propertyOrClassName): Relation;

    /**
     * Get all relation properties.
     *
     * @return array<string,Relation>
     */
    public function getRelations(): iterable;

    /**
     * Get all non-relation properties.
     *
     * @return array<string,Property>
     */
    public function getProperties(): iterable;

    /**
     * Get SQL column name for property.
     *
     * If property does not exists, it will return null. 
     */
    public function getColumnName(string $propertyName): ?string;

    /**
     * Get all non-relation property to column names map.
     *
     * @return array<string,string>
     *   Keys are property names, values are column names.
     */
    public function getColumnMap(): array;
}
