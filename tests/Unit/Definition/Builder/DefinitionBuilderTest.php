<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Definition\Registry\StaticEntityDefinitionRegistry;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Tests\Mock\WithManyToManyBarRelation;
use Goat\Mapper\Tests\Mock\WithManyToManyFooRelation;
use Goat\Mapper\Tests\Mock\WithManyToOneRelation;
use Goat\Mapper\Tests\Mock\WithOneToManyRelation;
use Goat\Mapper\Tests\Mock\WithoutRelation;
use PHPUnit\Framework\TestCase;

final class DefinitionBuilderTest extends TestCase
{
    private function createDefinitionRegistry(): DefinitionRegistry
    {
        return new CacheDefinitionRegistry(
            new ChainDefinitionRegistry([
                new StaticEntityDefinitionRegistry(),
            ])
        );
    }

    public function testSetTableName(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->setTableName('without_relation', 'foo');

        $definition = $builder->compile(
            $this->createDefinitionRegistry()
        );

        self::assertSame('without_relation', $definition->getTable()->getName());
        self::assertSame('foo', $definition->getTable()->getSchema());
    }

    public function testSetTableNameWithoutSchema(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->setTableName('without_relation');

        $definition = $builder->compile(
            $this->createDefinitionRegistry()
        );

        self::assertSame('without_relation', $definition->getTable()->getName());
        self::assertNull($definition->getTable()->getSchema());
    }

    public function testTableNameWithoutSetTableName(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $definition = $builder->compile(
            $this->createDefinitionRegistry()
        );

        self::assertSame('goat_mapper_tests_mock_withoutrelation', $definition->getTable()->getName());
    }

    public function testAddProperty(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->addProperty('id', 'id');
        $builder->addProperty('someProperty', 'some_property');
        $builder->addProperty('bla');

        $definition = $builder->compile(
            $this->createDefinitionRegistry()
        );

        self::assertSame(
            [
                'id' => 'id',
                'someProperty' => 'some_property',
                'bla' => 'bla',
            ],
            $definition->getColumnMap()
        );
    }

    public function testNonExistingClassNameRaiseError(): void
    {
        self::expectException(ConfigurationError::class);
        new DefinitionBuilder('WakaWakaHeHe');
    }

    public function testSetPrimaryKey()
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->addProperty('id');
        $builder->addProperty('bla');

        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);

        $definition = $builder->compile(
            $this->createDefinitionRegistry()
        );

        self::assertSame(
            ['id'],
            $definition->getPrimaryKey()->getColumnNames()
        );
        self::assertSame(
            'uuid',
            $definition->getPrimaryKey()->getColumns()[0]->getType()
        );
    }

    public function testSetPrimaryKeyWithoutPropertyRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/is not in defined in properties/');

        $builder->compile(
            $this->createDefinitionRegistry()
        );
    }

    public function testSetPrimaryKeyWithNumericKeysRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        self::expectException(ConfigurationError::class);
        $builder->setPrimaryKey([
            0 => 'uuid',
        ]);
    }

    public function testSetPrimaryKeyWithNonStringTypeRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        self::expectException(ConfigurationError::class);
        $builder->setPrimaryKey([
            'id' => new \DateTime(),
        ]);
    }

    public function testCreateRelationWithNonExistingClassRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/does not exist/');
        $builder->addAnyToOneRelation('relatedEntity', 'DansTesClasseurs');
    }

    /**
     * @deprecated I'm not sure whether or not keeping this test.
     */
    public function testSetPrimaryKeyAfterCompileRaiseError(): void
    {
        self::markTestIncomplete("Implement me");

        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->compile(
            $this->createDefinitionRegistry()
        );

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/already compiled/');

        $builder->compile(
            $this->createDefinitionRegistry()
        );
    }

    public function testCreateRelationOnExistingPropertyRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithManyToManyFooRelation::class);

        $builder->addProperty('relation');

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/is not a relation/');
        $builder->addManyToManyRelation('relation', WithManyToManyBarRelation::class);
    }

    public function testCreateRelationTwiceRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithManyToOneRelation::class);

        $builder->addOneToManyRelation('relatedCollection', WithOneToManyRelation::class);

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/is already defined/');
        $builder->addOneToManyRelation('relatedCollection', WithOneToManyRelation::class);
    }

    public function testAddPropertyThatDoesNotExistOnClassRaiseError(): void
    {
        self::markTestIncomplete("This is a placeholder for a future feature.");
    }

    public function testAddRelationWithPropertyThatDoesNotExistOnClassRaiseError(): void
    {
        self::markTestIncomplete("This is a placeholder for a future feature.");
    }
}
