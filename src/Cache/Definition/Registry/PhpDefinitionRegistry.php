<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Definition\Registry;

use Goat\Driver\ConfigurationError;
use Goat\Mapper\Cache\Definition\Dumper\PhpDumper;
use Goat\Mapper\Cache\Writer\FileWriter;
use Goat\Mapper\Cache\Writer\MemoryWriter;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Definition\Registry\DefinitionRegistryTrait;
use Goat\Mapper\Definition\Registry\WithParentDefinitionRegistry;

final class PhpDefinitionRegistry extends WithParentDefinitionRegistry
{
    use DefinitionRegistryTrait;

    private DefinitionRegistry $decorated;
    private string $generatedFileDirectory;

    public function __construct(DefinitionRegistry $decorated)
    {
        $this->decorated = $decorated;
    }

    public function setGeneratedFileDirectory(string $generatedFileDirectory): void
    {
        $this->generatedFileDirectory = $generatedFileDirectory;
    }

    protected function getGeneratedFileDirectory(): string
    {
        return $this->generatedFileDirectory ?? \sys_get_temp_dir();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $className): Entity
    {
        /**
         * @todo
         *   - check for class, if exists, go
         *   - compute a file name
         *   - check for file name existence, load it
         *   - check for class existence now (probably should be in the autoloader)
         *   - name must be generated using some kind of content hash: if data changes
         *     class name must too
         *   - no name, no class, generate it in given file
         *   - FIND A FOO-KING WAY to inject this instance to loaders, for proxifying
         *     succequent entity load calls
         */

        $function = $this->getFunction($className);

        return $function($this->getParentDefinitionRegistry());
    }

    private function getFunction(string $className): string
    {
        $functionName = $this->getFunctionName($className);

        if (!\function_exists($functionName)) {
            $filename = $this->getFileName($className);

            if (false === @include $filename) {
                // File does not seem to exists.
                if (\file_exists($filename)) {
                    throw new ConfigurationError(\sprintf(
                        "Could not include existing file %s: %s",
                        $filename,
                        (\error_get_last() ?? ['message' => "<no message>"])['message']
                    ));
                }

                $this->generateFunction($className, $functionName, $filename);

                if (false === @include $filename) {
                    if (\file_exists($filename)) {
                        throw new ConfigurationError(\sprintf(
                            "Could not include existing file %s: %s",
                            $filename,
                            (\error_get_last() ?? ['message' => "<no message>"])['message']
                        ));
                    } else {
                        throw new ConfigurationError(\sprintf(
                            "Could not generate file %s: %s",
                            $filename,
                            (\error_get_last() ?? ['message' => "<no message>"])['message']
                        ));
                    }
                }

                if (!\function_exists($functionName)) {
                    throw new ConfigurationError(\sprintf(
                        "Function %s was not properly generated in file %s",
                        $functionName,
                        $filename
                    ));
                }
            }
        }

        return $functionName;
    }

    private function generateFunction(string $className, string $functionName, string $filename): void
    {
        $entity = $this->decorated->getDefinition($className);

        $writer = new MemoryWriter($filename);

        $writer->write("<?php");
        $writer->write("declare(strict_types=1);");
        $writer->newline();

        $writer->write("function " . $functionName . "(\\" . DefinitionRegistry::class . " \$registry): \\" . Entity::class);
        $writer->write("{");
        $writer->indentationInc();
        $dumper = new PhpDumper();
        $dumper->dump($entity, $writer);
        $writer->indentationDec();
        $writer->write("}");

        $content = $writer->closeAndGetBuffer();

        $realWriter = new FileWriter($filename);
        $realWriter->write($content);
        $realWriter->close();
    }

    private function getFileName(string $className): string
    {
        return $this->getGeneratedFileDirectory().'/GoatMapperGeneratedDefinition_'.$this->getClassNameHash($className) . '.php';
    }

    private function getFunctionName(string $className): string
    {
        return 'goatMapperGetEntity' . $this->getClassNameHash($className);
    }

    private function getClassNameHash(string $className): string
    {
        return \sha1($className);
    }
}

/**
 * This could simply generate functions instead of classes such as the example
 * below.
 *
 * Then, just write those each in one file, in some cache directory, and add
 * it to composer autoload file map.
 *
function getEntityMyVendorModelEntitySomeHash(DefinitionRegistry $parent): Entity
{
    $entity = new DefaultEntity('Foo');
    // $entity->ssetTruc();
    // ...

    $entity->addProperty(
        new DefaultRelationAnyToOne(
            new EntityProxy(
                'TargetClassName',
                static function () use ($parent): Entity {
                    return $parent->getDefinition('TargetClassName');
                }
            ),
            'someProperty'
        )
    );

    return $entity;
}
 */
