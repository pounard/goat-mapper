<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder;

use Goat\Mapper\Definition\Column;
use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Graph\Impl\EntityProxy;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\ConfigurationError;

trait BuilderTrait
{
    protected function ensureKeysCompatibility(Key $a, Key $b, ?string $message = null): void
    {
        if (!$a->isCompatible($b)) {
            throw new ConfigurationError(\sprintf(
                $message ?? "Relation for property '%s' source key is not compatible with target key.",
                __CLASS__
            ));
        }
    }

    protected function ensureKeyIsValid(array $propertyTypeMap): void
    {
        foreach ($propertyTypeMap as $propertyName => $type) {
            if (!\is_string($propertyName)) {
                throw new ConfigurationError("Primary key property names must be strings");
            }
            if (!\is_string($type)) {
                throw new ConfigurationError("Primary key property types must be strings");
            }
        }
    }

    protected function ensureClassExists(string $className): void
    {
        if (!\class_exists($className)) {
            throw new ConfigurationError(\sprintf("Class '%s' does not exist", $className));
        }
    }

    protected function normalizeName(string $name): string
    {
        return \strtolower(\preg_replace('/[^a-z0-9]+/i', '_', $this->className));
    }

    protected function doCompileColumnList(array $source, ?array $nameMap = null): array
    {
        $ret = [];
        foreach ($source as $propertyName => $type) {
            if (null !== $nameMap) {
                $name = $nameMap[$propertyName] ?? null;
            } else {
                $name = $propertyName;
            }
            if (null === $name) {
                throw new ConfigurationError(\sprintf("Primary key component '%s' is not in defined in properties", $propertyName));
            }
            $ret[] = new Column($name, $type);
        }
        return $ret;
    }

    protected function doCompileKey(array $source, ?array $nameMap = null): Key
    {
        return new Key($this->doCompileColumnList($source, $nameMap));
    }

    protected function doCompilePrimaryKey(array $source, ?array $nameMap = null): PrimaryKey
    {
        return new PrimaryKey($this->doCompileColumnList($source, $nameMap));
    }

    protected function createProxy(string $className, DefinitionRegistry $definitionRegistry): EntityProxy
    {
        return new EntityProxy($className, static function () use ($className, $definitionRegistry) {
            return $definitionRegistry->getDefinition($className);
        });
    }
}
