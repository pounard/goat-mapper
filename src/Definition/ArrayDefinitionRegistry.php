<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

use Goat\Mapper\Error\InvalidRepositoryDefinitionError;
use Goat\Mapper\Error\RepositoryDoesNotExistError;

/**
 * Array specification for input:
 *
 * [
 *     [
 *         'class_name' => FQDN,
 *         'property_name' => ENTITY-PROPERTY-NAME,
 *         'table' => SQL-TABLE-NAME,
 *         ('schema' => SQL-TABLE-SCHEMA,)
 *         'primary-key' => [
 *             SQL-COLUMN-NAME => SQL-TYPE,
 *             (...,)
 *         ],
 *         'columns' => [
 *              ENTITY-PROPERTY-NAME => SQL-COLUMN-NAME,
 *         ],
 *         'relations' => [
 *             [
 *                 'class_name' => CLASS-FQDN (repository MUST exist),
 *                 'table' => SQL-TABLE-NAME,
 *                 ('schema' => SQL-TABLE-SCHEMA,)
 *                 'mode' => "one_to_one" | "one_to_many" | "many_to_one" | "many_to_many",
 *                 'target_key' => [
 *                     SQL-COLUMN-NAME => SQL-TYPE,
 *                     (...,)
 *                 ],
 *                 ('key_in' => "mapping" | "source" | "target",)
 *                 ('source_key' => [
 *                     SQL-COLUMN-NAME => SQL-TYPE,
 *                     (...,)
 *                 ],)
 *             ],
 *             (...,)
 *         ],
 *     ],
 *     (...,)
 * ]
 */
class ArrayDefinitionRegistry implements DefinitionRegistry
{
    /** @var ?string */
    private $defaultSchema = 'public';

    /** @var RepositoryDefinition[] */
    private $repositories = [];

    /** @var array */
    private $userData;

    public function __construct(array $userData, ?string $defaultSchema = 'public')
    {
        $this->defaultSchema = $defaultSchema;
        $this->userData = $userData;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryDefinition(string $className): RepositoryDefinition
    {
        return $this->repositories[$className] ?? (
            $this->repositories[$className] = $this->createRepositoryDefinition($className)
        );
    }

    private function createTable(string $name, ?string $schema): Table
    {
        return new Table($name, $schema ?? $this->defaultSchema);
    }

    /** @return array<string,string> */
    private function parseKeyValueArray(array $input): array
    {
        foreach ($input as $key => $value) {
            if (!\is_string($key)) {
                throw new InvalidRepositoryDefinitionError(\sprintf("Key must be a string"));
            }
            if (!\is_string($value)) {
                throw new InvalidRepositoryDefinitionError(\sprintf("Value must be a string"));
            }
        }
        return $input;
    }

    /** @return Column[] */
    private function parseColumnArray(array $input): array
    {
        $ret = [];
        foreach ($input as $name => $type) {
            if (!\is_string($name)) {
                throw new InvalidRepositoryDefinitionError(\sprintf("Column name must be a string"));
            }
            $ret[] = new Column($name, (string)$type);
        }
        return $ret;
    }

    private function valueToMode(string $value): int
    {
        if ('one_to_one' === $value) {
            return Relation::MODE_ONE_TO_ONE;
        }
        if ('one_to_many' === $value) {
            return Relation::MODE_ONE_TO_MANY;
        }
        if ('many_to_one' === $value) {
            return Relation::MODE_MANY_TO_ONE;
        }
        if ('many_to_many' === $value) {
            return Relation::MODE_MANY_TO_MANY;
        }
        throw new InvalidRepositoryDefinitionError(\sprintf(
            "Relation mode must be one of '%s', got '%s'.",
            \implode("', '", ['one_to_one', 'one_to_many', 'many_to_one', 'many_to_many']), $value
        ));
    }

    private function valueToKeyIn(string $value): int
    {
        if ('mapping' === $value) {
            return Relation::KEY_IN_MAPPING;
        }
        if ('source' === $value) {
            return Relation::KEY_IN_SOURCE;
        }
        if ('target' === $value) {
            return Relation::KEY_IN_TARGET;
        }
        throw new InvalidRepositoryDefinitionError(\sprintf(
            "Key in must be one of '%s', got '%s'.",
            \implode("', '", ['mapping', 'source', 'target']), $value
        ));
    }

    private function parseRelation(array $input, Table $sourceTable, PrimaryKey $sourceKey): Relation
    {
        $propertyName = $input['property_name'] ?? null;
        if (!$propertyName) {
            throw new InvalidRepositoryDefinitionError("Missing 'property_name' in relation definition");
        }

        $className = $input['class_name'] ?? null;
        if (!$className) {
            throw new InvalidRepositoryDefinitionError("Missing 'class_name' in relation definition");
        }
        if (!\class_exists($className) && !\interface_exists($className)) {
            throw new InvalidRepositoryDefinitionError(\sprintf("Class or interface %s does not exists.", $className));
        }

        if (empty($input['table'])) {
            throw new InvalidRepositoryDefinitionError(\sprintf("Relation to class %s has no table set.", $className));
        }
        if (empty($input['target_key'])) {
            throw new InvalidRepositoryDefinitionError(\sprintf("Relation to class %s has no target_key set.", $className));
        }

        return new Relation(
            $propertyName,
            $className,
            $this->valueToMode($input['mode'] ?? 'null'),
            $this->createTable($input['table'], $input['schema'] ?? null),
            $sourceTable,
            new Key($this->parseColumnArray($input['target_key'])),
            isset($input['source_key']) ? new Key($this->parseColumnArray($input['source_key'])) : $sourceKey,
            isset($input['key_in']) ? $this->valueToKeyIn($input['key_in']) : null,
        );
    }

    /** @return Relation[] */
    private function parseRelationArray(array $input, Table $sourceTable, PrimaryKey $sourceKey): array
    {
        $ret = [];
        foreach ($input as $data) {
            $ret[] = $this->parseRelation($data, $sourceTable, $sourceKey);
        }
        return $ret;
    }

    private function createRepositoryDefinition(string $className): RepositoryDefinition
    {
        if (!\class_exists($className) && !\interface_exists($className)) {
            throw new InvalidRepositoryDefinitionError(\sprintf("Class or interface %s does not exists.", $className));
        }

        if (!isset($this->userData[$className])) {
            throw new RepositoryDoesNotExistError(\sprintf("There is no known registery for class %s.", $className));
        }
        $input = $this->userData[$className];

        if (empty($input['table'])) {
            throw new InvalidRepositoryDefinitionError(\sprintf("Repository for class %s has no table set.", $className));
        }

        $table = $this->createTable((string)$input['table'], $input['schema'] ?? null);
        $primaryKey = new PrimaryKey($this->parseColumnArray($input['primary_key'] ?? []));

        return new RepositoryDefinition(
            new EntityDefinition(
                $className,
                $this->parseKeyValueArray($input['columns'] ?? [])
            ),
            $table,
            $primaryKey,
            $this->parseRelationArray($input['relations'] ?? [], $table, $primaryKey)
        );
    }
}
