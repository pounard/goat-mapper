<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Definition\Registry;

use Goat\Mapper\Cache\GeneratorConfiguration;
use Goat\Mapper\Cache\Definition\Dumper\PhpDumper;
use Goat\Mapper\Cache\Writer\MemoryWriter;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Definition\Registry\DefinitionRegistryTrait;
use Goat\Mapper\Definition\Registry\WithParentDefinitionRegistry;

class PhpDefinitionRegistry extends WithParentDefinitionRegistry
{
    use DefinitionRegistryTrait;

    private DefinitionRegistry $decorated;
    private ?GeneratorConfiguration $generatorConfiguration = null;

    public function __construct(DefinitionRegistry $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function setGeneratedFileDirectory(string $generatedFileDirectory): void
    {
        $this->getGeneratorConfiguration()->setGeneratedClassDirectory($generatedFileDirectory);
    }

    public function setGeneratorConfiguration(GeneratorConfiguration $configuration): void
    {
        $this->generatorConfiguration = $configuration;
    }

    protected function getGeneratorConfiguration(): GeneratorConfiguration
    {
        return $this->generatorConfiguration ?? (
            $this->generatorConfiguration = new GeneratorConfiguration()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $className): Entity
    {
        return \call_user_func(
            [
                $this->getClassName($className),
                'getDefinition',
            ],
            $this->getParentDefinitionRegistry()
        );
    }

    private function getClassName(string $className): string
    {
        $configuration = $this->getGeneratorConfiguration();

        $generatedClassName = $configuration
            ->getClassNameInflector()
            ->getGeneratedClassName($className)
        ;

        if (\class_exists($generatedClassName)) {
            return $generatedClassName;
        }

        $configuration
            ->getGeneratorStrategy()
            ->generate(
                $generatedClassName,
                $this->generateClass(
                    $className,
                    $generatedClassName
                )
            )
        ;

        return $generatedClassName;
    }

    private function generateClass(string $className, string $generatedClassName): string
    {
        $entity = $this->decorated->getDefinition($className);

        $writer = new MemoryWriter();

        $lastSeparatorPos = \strrpos($generatedClassName, '\\');
        $generatedClassNamespace = \substr($generatedClassName, 0, $lastSeparatorPos);
        $generatedClassLocalName = \substr($generatedClassName, $lastSeparatorPos + 1);

        $writer->write("namespace $generatedClassNamespace;");
        $writer->newline();
        $writer->write("final class {$generatedClassLocalName}");
        $writer->write("{");
        $writer->indentationInc();

        $writer->write("public static function getDefinition(\\" . DefinitionRegistry::class . " \$registry): \\" . Entity::class);
        $writer->write("{");
        $writer->indentationInc();
        $dumper = new PhpDumper();
        $dumper->dump($entity, $writer);
        $writer->indentationDec();
        $writer->write("}");

        $writer->indentationDec();
        $writer->write("}");

        return $writer->closeAndGetBuffer();
    }
}
