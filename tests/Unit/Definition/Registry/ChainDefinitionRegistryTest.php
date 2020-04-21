<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition\Registry;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Impl\DefaultEntity;
use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\EntityDoesNotExistError;
use PHPUnit\Framework\TestCase;

final class ChainDefinitionRegistryTest extends TestCase
{
    private function createChainedInstances(): iterable
    {
        yield new class () implements DefinitionRegistry {
            public function getDefinition(string $className): Entity
            {
                if ('Foo' === $className) {
                    return new DefaultEntity('Foo');
                }
                throw new EntityDoesNotExistError();
            }
        };

        yield new class () implements DefinitionRegistry {
            public function getDefinition(string $className): Entity
            {
                if ('Bar' === $className) {
                    return new DefaultEntity('Bar');
                }
                throw new EntityDoesNotExistError();
            }
        };
    }

    private function doTestAll(ChainDefinitionRegistry $definitionRegistry): void
    {
        $entity = $definitionRegistry->getDefinition('Foo');

        self::assertSame('Foo', $entity->getClassName());

        $entity = $definitionRegistry->getDefinition('Bar');

        self::assertSame('Bar', $entity->getClassName());

        self::expectException(EntityDoesNotExistError::class);

        $definitionRegistry->getDefinition('Baz');
    }

    public function testAllWithConstructorInjection(): void
    {
        $definitionRegistry = new ChainDefinitionRegistry(
            $this->createChainedInstances()
        );

        $this->doTestAll($definitionRegistry);
    }

    public function testAllWithSetterInjection(): void
    {
        $definitionRegistry = new ChainDefinitionRegistry();
        foreach ($this->createChainedInstances() as $instance) {
            $definitionRegistry->add($instance);
        }

        $this->doTestAll($definitionRegistry);
    }
}