<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition\Registry;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Impl\DefaultEntity;
use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\EntityDoesNotExistError;
use PHPUnit\Framework\TestCase;

final class CacheDefinitionRegistryTest extends TestCase
{
    public function testGetCalledOnlyOnceForHitsAndMisses(): void
    {
        $decorated = new class () implements DefinitionRegistry {

            private $count = 0;

            public function getCount(): int
            {
                return $this->count;
            }

            public function getDefinition(string $className): Entity
            {
                ++$this->count;

                if ('Foo' === $className) {
                    return new DefaultEntity('Foo');
                }

                throw new EntityDoesNotExistError('Pouet');
            }
        };

        $definitionRegistry = new CacheDefinitionRegistry($decorated);

        self::assertSame(0, $decorated->getCount());

        $entity = $definitionRegistry->getDefinition('Foo');

        self::assertSame(1, $decorated->getCount());

        self::assertSame($entity, $definitionRegistry->getDefinition('Foo'));

        self::assertSame(1, $decorated->getCount());

        try {
            $definitionRegistry->getDefinition('Bar');
            self::fail();
        } catch (EntityDoesNotExistError $e) {
            self::assertNotSame('Pouet', $e->getMessage());
        }

        self::assertSame(2, $decorated->getCount());

        try {
            $definitionRegistry->getDefinition('Bar');
            self::fail();
        } catch (EntityDoesNotExistError $e) {
            self::assertNotSame('Pouet', $e->getMessage());
        }

        self::assertSame(2, $decorated->getCount());
    }
}
