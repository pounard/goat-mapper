<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition;

use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Definition\Registry\StaticEntityDefinitionRegistry;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithManyToOneRelation;
use Goat\Mapper\Tests\Mock\WithOneToManyRelation;

final class DefinitionBuilderGraphTest extends AbstractRepositoryTest
{
    private function createDefinitionRegistry(): DefinitionRegistry
    {
        $definitionRegistry = new CacheDefinitionRegistry(
            new ChainDefinitionRegistry([
                $staticEntityDefinitionRegistry = new StaticEntityDefinitionRegistry(),
            ])
        );

        $staticEntityDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);

        return $definitionRegistry;
    }

    public function testOneToManyRelation(): void
    {
        $definitionRegistry = $this->createDefinitionRegistry();

        $entity = $definitionRegistry->getDefinition(WithOneToManyRelation::class);
        $target = $definitionRegistry->getDefinition(WithManyToOneRelation::class);

        self::assertSame(
            $entity->getTable(),
            $target->getRelation(WithOneToManyRelation::class)->getEntity()->getTable()
        );

        self::assertSame(
            $target->getTable(),
            $target->getRelation(WithOneToManyRelation::class)->getOwner()->getTable()
        );

        self::assertSame(
            $target->getTable(),
            $entity->getRelation(WithManyToOneRelation::class)->getEntity()->getTable()
        );

        self::assertSame(
            $entity->getTable(),
            $entity->getRelation(WithManyToOneRelation::class)->getOwner()->getTable()
        );
    }

    public function testManytoManyRelation(): void
    {
        self::markTestIncomplete();
    }
}
