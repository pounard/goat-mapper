<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Definition\Dumper;

use Goat\Converter\ConverterInterface;
use Goat\Mapper\Cache\Writer\Writer;
use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Property;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationAnyToOne;
use Goat\Mapper\Definition\Graph\RelationManyToMany;
use Goat\Mapper\Definition\Graph\RelationOneToMany;
use Goat\Mapper\Definition\Graph\Value;
use Goat\Mapper\Definition\Graph\Impl\EntityProxy;
use Goat\Mapper\Error\ConfigurationError;

final class PhpDumper
{
    public function dump(Entity $entity, Writer $writer)
    {
        $this->dumpEntity($entity, $writer);
    }

    private function dumpEntity(Entity $entity, Writer $writer): void
    {
        $className = $entity->getClassName();

        $writer->write("\$ret = new \Goat\Mapper\Definition\Graph\Impl\DefaultEntity(\\{$className}::class);");
        $writer->newline();

        $this->dumpTable($entity->getTable(), $writer);
        $writer->write("\$ret->setTable(\$table);");
        $writer->newline();

        $this->dumpPrimaryKey($entity->getPrimaryKey(), $writer);
        $writer->write("\$ret->setPrimaryKey(\$key);");
        $writer->newline();

        foreach ($entity->getProperties() as $child) {
            $this->dumpProperty($child, $writer);
        }
        foreach ($entity->getRelations() as $child) {
            $this->dumpRelation($child, $writer);
        }

        $writer->newline();
        $writer->write("return \$ret;");
    }

    private function dumpProperty(Property $property, Writer $writer): void
    {
        if ($property instanceof Value) {
            $this->dumpValue($property, $writer);
        } else {
            // @codeCoverageIgnoreStart
            throw new ConfigurationError(\sprintf("Cannot dump entity property with class '%s'", \get_class($property)));
            // @codeCoverageIgnoreEnd
        }
    }

    private function dumpValue(Value $value, Writer $writer): void
    {
        $name = $this->exportString($value->getName());
        $columnName = $this->exportString($value->getColumnName());
        $type = $this->exportType($value->getColumnType());
        $writer->write("\$ret->addProperty(new \Goat\Mapper\Definition\Graph\Impl\DefaultValue({$name}, {$columnName}, {$type}));");
    }

    private function dumpRelation(Relation $relation, Writer $writer): void
    {
        $writer->newline();

        if ($relation instanceof RelationAnyToOne) {
            $this->dumpRelationAnyToOne($relation, $writer);
        } else if ($relation instanceof RelationOneToMany) {
            $this->dumpRelationOneToMany($relation, $writer);
        } else if ($relation instanceof RelationManyToMany) {
            $this->dumpRelationManyToMany($relation, $writer);
        } else {
            // @codeCoverageIgnoreStart
            throw new ConfigurationError(\sprintf("Cannot dump entity relation with class '%s'", \get_class($relation)));
            // @codeCoverageIgnoreEnd
        }
    }

    private function dumpRelationAnyToOne(RelationAnyToOne $relation, Writer $writer): void
    {
        $className = $relation->getClassName();
        $this->dumpEntityProxy($className, $writer, '$proxy');

        $name = $this->exportString($relation->getName());
        $mode = $relation->getMode();
        $writer->write("\$relation = new \Goat\Mapper\Definition\Graph\Impl\DefaultRelationAnyToOne(");
        $writer->indentationInc();
        $writer->write("\$proxy,");
        $writer->write($name . ",");
        $writer->write("\\" . $className . "::class,");
        $writer->write((string)$mode);
        $writer->indentationDec();
        $writer->write(");");

        $this->dumpRelationCommons($relation, $writer);

        $writer->write("\$ret->addProperty(\$relation);");
    }

    private function dumpRelationOneToMany(RelationOneToMany $relation, Writer $writer): void
    {
        $className = $relation->getClassName();
        $this->dumpEntityProxy($className, $writer, '$proxy');

        $name = $this->exportString($relation->getName());
        $writer->write("\$relation = new \Goat\Mapper\Definition\Graph\Impl\DefaultRelationOneToMany(");
        $writer->indentationInc();
        $writer->write("\$proxy,");
        $writer->write($name . ",");
        $writer->write("\\" . $className . "::class");
        $writer->indentationDec();
        $writer->write(");");

        $this->dumpRelationCommons($relation, $writer);

        $writer->write("\$ret->addProperty(\$relation);");
    }

    private function dumpRelationManyToMany(RelationManyToMany $relation, Writer $writer): void
    {
        $className = $relation->getClassName();
        $this->dumpEntityProxy($className, $writer, '$proxy');

        $name = $this->exportString($relation->getName());
        $writer->write("\$relation = new \Goat\Mapper\Definition\Graph\Impl\DefaultRelationManyToMany(");
        $writer->indentationInc();
        $writer->write("\$proxy,");
        $writer->write($name . ",");
        $writer->write("\\" . $className . "::class");
        $writer->indentationDec();
        $writer->write(");");

        $this->dumpRelationCommons($relation, $writer);

        $this->dumpTable($relation->getMappingTable(), $writer, '$table');
        $writer->write("\$relation->setMappingTable(\$table);");

        if ($relation->hasMappingSourceKey()) {
            $this->dumpKey($relation->getMappingSourceKey(), $writer, '$mappingSourceKey');
            $writer->write("\$relation->setMappingSourceKey(\$mappingSourceKey);");
        }

        if ($relation->hasMappingTargetKey()) {
            $this->dumpKey($relation->getMappingTargetKey(), $writer, '$mappingTargetKey');
            $writer->write("\$relation->setMappingTargetKey(\$mappingTargetKey);");
        }

        $writer->write("\$ret->addProperty(\$relation);");
    }

    private function dumpRelationCommons(Relation $relation, Writer $writer, string $variableName = '$relation'): void
    {
        $allowsNull = $this->exportBool($relation->allowsNull());
        $writer->write("\$relation->setOwner(\$ret);");
        $writer->write("\$relation->setAllowsNull(" . $allowsNull . ");");

        if ($relation->hasSourceKey()) {
            $this->dumpKey($relation->getSourceKey(), $writer, '$sourceKey');
            $writer->write("\$relation->setSourceKey(\$sourceKey);");
        }

        if ($relation->hasTargetKey()) {
            $this->dumpKey($relation->getTargetKey(), $writer, '$targetKey');
            $writer->write("\$relation->setTargetKey(\$targetKey);");
        }
    }

    private function dumpEntityProxy(string $className, Writer $writer, string $variableName = '$proxy')
    {
        $classNameStatement = "\\" . $className . "::class";
        $writer->write($variableName . " = new \\" . EntityProxy::class . "(");
        $writer->indentationInc();
        $writer->write($classNameStatement . ",");
        $writer->write("static function () use (\$registry) {");
        $writer->indentationInc();
        $writer->write("return \$registry->getDefinition(" . $classNameStatement . ");");
        $writer->indentationDec();
        $writer->write("}");
        $writer->indentationDec();
        $writer->write(");");
    }

    private function dumpTable(Table $table, Writer $writer, string $variableName = '$table'): void
    {
        $name = $this->exportString($table->getName());
        $schema = $this->exportString($table->getSchema());
        $writer->write($variableName . " = new \Goat\Mapper\Definition\Table(" . $name . ", " . $schema . ");");
    }

    private function dumpPrimaryKey(PrimaryKey $key, Writer $writer, string $variableName = '$key'): void
    {
        $writer->write($variableName . " = new \Goat\Mapper\Definition\PrimaryKey([");
        $this->writeKeyColumns($key, $writer);
        $writer->write("]);");
    }

    private function dumpKey(Key $key, Writer $writer, string $variableName = '$key'): void
    {
        $writer->write($variableName . " = new \Goat\Mapper\Definition\Key([");
        $this->writeKeyColumns($key, $writer);
        $writer->write("]);");
    }

    private function writeKeyColumns(Key $key, Writer $writer): void
    {
        foreach ($key->getColumns() as $column) {
            $writer->indentationInc();

            $name = $this->exportString($column->getName());
            $type = $this->exportType($column->getType());

            $writer->write("new \Goat\Mapper\Definition\Column(" . $name . ", " . $type . ")");
            $writer->indentationDec();
        }
    }

    private function exportType(?string $type): string
    {
        if (!$type || ConverterInterface::TYPE_UNKNOWN === $type) {
            return 'null';
        } else {
            return $this->exportString($type);
        }
    }

    private function exportBool($value): string
    {
        return $value ? 'true' : 'false';
    }

    private function exportString(?string $string): ?string
    {
        return null === $string ? 'null' : \var_export($string, true);
    }
}
