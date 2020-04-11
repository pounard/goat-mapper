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
    private function ensureKeysCompatibility(Key $a, Key $b): void
    {
        if (!$a->isCompatible($b)) {
            throw new ConfigurationError(\sprintf(
                "Relation for property '%s' source key is not compatible with target key.",
                __CLASS__
            ));
        }
    }

    private function ensureKeyIsValid(array $propertyTypeMap): void
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

    private function ensureClassExists(string $className): void
    {
        if (!\class_exists($className)) {
            throw new ConfigurationError(\sprintf("Class '%s' does not exist", $className));
        }
    }

    private function normalizeName(string $name): string
    {
        return \strtolower(\preg_replace('/[^a-z0-9]+/i', '_', $this->className));
    }

    private function lazy(string $methodName): callable
    {
        return function () use ($methodName) {
            return \call_user_func([$this, $methodName]);
        };
    }

    private function doCompileColumnList(array $source, ?array $nameMap = null): array
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

    private function doCompileKey(array $source, ?array $nameMap = null): Key
    {
        return new Key($this->doCompileColumnList($source, $nameMap));
    }

    private function doCompilePrimaryKey(array $source, ?array $nameMap = null): PrimaryKey
    {
        return new PrimaryKey($this->doCompileColumnList($source, $nameMap));
    }

    private function createProxy(string $className, DefinitionRegistry $definitionRegistry): EntityProxy
    {
        return new EntityProxy($className, static function () use ($className, $definitionRegistry) {
            return $definitionRegistry->getDefinition($className);
        });
    }
}
