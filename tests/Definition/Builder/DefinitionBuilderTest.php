<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Definition;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Tests\Mock\WithToManyInMappingRelation;
use Goat\Mapper\Tests\Mock\WithoutRelation;
use PHPUnit\Framework\TestCase;

final class DefinitionBuilderTest extends TestCase
{
    public function testSetTableName(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->setTableName('without_relation', 'foo');

        $definition = $builder->compile();
        self::assertSame('without_relation', $definition->getTable()->getName());
        self::assertSame('foo', $definition->getTable()->getSchema());
    }

    public function testSetTableNameWithoutSchema(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->setTableName('without_relation');

        $definition = $builder->compile();
        self::assertSame('without_relation', $definition->getTable()->getName());
        self::assertNull($definition->getTable()->getSchema());
    }

    public function testTableNameWithoutSetTableName(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $definition = $builder->compile();
        self::assertSame('goat_mapper_tests_mock_withoutrelation', $definition->getTable()->getName());
    }

    public function testAddProperty(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->addProperty('id', 'id');
        $builder->addProperty('someProperty', 'some_property');
        $builder->addProperty('bla');

        $definition = $builder->compile();
        self::assertSame(
            [
                'id' => 'id',
                'someProperty' => 'some_property',
                'bla' => 'bla',
            ],
            $definition->getEntityDefinition()->getColumnMap()
        );
    }

    public function testNonExistingClassNameRaiseError(): void
    {
        self::expectException(ConfigurationError::class);
        new DefinitionBuilder('WakaWakaHeHe');
    }

    public function testSetPrimaryKeyWithoutPropertyRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/is not in defined in properties/');
        $builder->compile();
    }

    public function testSetPrimaryKeyWithNumericKeysRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        self::expectException(ConfigurationError::class);
        $builder->setPrimaryKey([
            0 => 'uuid',
        ]);
    }

    public function testSetPrimaryKeyAfterCompileRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->compile();

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/already compiled/');
        $builder->setPrimaryKey([]);
    }

    public function testSetPrimaryKeyWithNonStringTypeRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        self::expectException(ConfigurationError::class);
        $builder->setPrimaryKey([
            'id' => new \DateTime(),
        ]);
    }

    public function testCreateAnArbitraryRelationPropagatesLazyValues(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $relation = $builder->addManyToOneRelation('relatedEntity', WithToManyInMappingRelation::class);
        $relation->setTargetTableName('target_table');
        $relation->setTargetKey(['target_id' => 'int']);

        $builder->addProperty('id');
        $builder->setPrimaryKey([
            'id' => 'int',
        ]);
        $builder->setTableName('source_table');

        $definition = $builder->compile();
        $relation = $definition->getRelation('relatedEntity');
        self::assertSame(['id'], $relation->getSourceKey()->getColumnNames());
        self::assertSame(['target_id'], $relation->getTargetKey()->getColumnNames());
        self::assertSame('source_table', $relation->getSourceTable()->getName());
        self::assertSame('target_table', $relation->getTargetTable()->getName());
    }

    public function testCreateRelationWithNonExistingClassRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/does not exist/');
        $builder->addManyToOneRelation('relatedEntity', 'DansTesClasseurs');
    }

    public function testCreateRelationOnExistingPropertyRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->addProperty('relatedEntity');

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/is not a relation/');
        $builder->addManyToOneRelation('relatedEntity', WithToManyInMappingRelation::class);
    }

    public function testCreateRelationTwiceRaiseError(): void
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->addOneToManyRelation('relatedEntity', WithToManyInMappingRelation::class);

        self::expectException(ConfigurationError::class);
        self::expectExceptionMessageRegExp('/is already defined/');
        $builder->addManyToOneRelation('relatedEntity', WithToManyInMappingRelation::class);
    }

    public function testSetPrimaryKey()
    {
        $builder = new DefinitionBuilder(WithoutRelation::class);

        $builder->addProperty('id');
        $builder->addProperty('bla');

        $builder->setPrimaryKey([
            'id' => 'uuid',
        ]);

        $definition = $builder->compile();
        self::assertSame(
            ['id'],
            $definition->getPrimaryKey()->getColumnNames()
        );
        self::assertSame(
            'uuid',
            $definition->getPrimaryKey()->getColumns()[0]->getType()
        );
    }
}
