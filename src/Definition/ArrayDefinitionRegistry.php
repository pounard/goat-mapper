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
 *         'relations' => [
 *             [
 *                 'class_name' => CLASS-FQDN (repository MUST exist),
 *                 'table' => SQL-TABLE-NAME,
 *                 ('schema' => SQL-TABLE-SCHEMA,)
 *                 'mode' => "one_to_one" | "one_to_many" | "many_to_one" | "many_to_many",
 *                 'key' => [
 *                     SQL-COLUMN-NAME => SQL-TYPE,
 *                     (...,)
 *                 ],
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

    protected function getTable(string $name, ?string $schema): Table
    {
        return new Table($name, $schema ?? $this->defaultSchema);
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

    private function parseRelation(array $input): Relation
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

        return new Relation(
            $propertyName,
            $className,
            $this->valueToMode($input['mode'] ?? 'null'),
            $this->getTable($input['table'], $input['schema'] ?? null),
            new Key($this->parseColumnArray($input['key'] ?? []))
        );
    }

    /** @return Relation[] */
    private function parseRelationArray(array $input): array
    {
        $ret = [];
        foreach ($input as $data) {
            $ret[] = $this->parseRelation($data);
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

        return new RepositoryDefinition(
            new EntityDefinition($className),
            $this->getTable((string)$input['table'], $input['schema'] ?? null),
            new PrimaryKey($this->parseColumnArray($input['primary_key'] ?? [])),
            $this->parseRelationArray($input['relations'] ?? [])
        );
    }
}
