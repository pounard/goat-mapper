<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition;

use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Definition\Registry\StaticEntityDefinitionRegistry;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithManyToManyBarRelation;
use Goat\Mapper\Tests\Mock\WithManyToManyFooRelation;
use Goat\Mapper\Tests\Mock\WithManyToOneRelation;
use Goat\Mapper\Tests\Mock\WithOneToManyRelation;

final class DefinitionBuilderGraphTest extends AbstractRepositoryTest
{
    protected function createDefinitionRegistry(): DefinitionRegistry
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
            $target->getRelation('relatedEntity')->getEntity()->getTable()
        );

        self::assertSame(
            $entity->getTable(),
            $target->getRelation('relatedEntityUsingSerial')->getEntity()->getTable()
        );

        self::assertSame(
            $target->getTable(),
            $target->getRelation('relatedEntity')->getOwner()->getTable()
        );

        self::assertSame(
            $target->getTable(),
            $target->getRelation('relatedEntityUsingSerial')->getOwner()->getTable()
        );

        self::assertSame(
            $target->getTable(),
            $entity->getRelation('relatedCollection')->getEntity()->getTable()
        );

        self::assertSame(
            $target->getTable(),
            $entity->getRelation('relatedCollectionUsingSerial')->getEntity()->getTable()
        );

        self::assertSame(
            $entity->getTable(),
            $entity->getRelation('relatedCollection')->getOwner()->getTable()
        );

        self::assertSame(
            $entity->getTable(),
            $entity->getRelation('relatedCollectionUsingSerial')->getOwner()->getTable()
        );
    }

    public function testManytoManyRelation(): void
    {
        $definitionRegistry = $this->createDefinitionRegistry();

        $entity = $definitionRegistry->getDefinition(WithManyToManyFooRelation::class);
        $target = $definitionRegistry->getDefinition(WithManyToManyBarRelation::class);

        self::assertSame(
            $entity->getTable(),
            $target->getRelation(WithManyToManyFooRelation::class)->getEntity()->getTable()
        );

        self::assertSame(
            $target->getTable(),
            $target->getRelation(WithManyToManyFooRelation::class)->getOwner()->getTable()
        );

        self::assertSame(
            $target->getTable(),
            $entity->getRelation(WithManyToManyBarRelation::class)->getEntity()->getTable()
        );

        self::assertSame(
            $entity->getTable(),
            $entity->getRelation(WithManyToManyBarRelation::class)->getOwner()->getTable()
        );
    }
}
