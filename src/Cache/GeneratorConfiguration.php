<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache;

use Goat\Mapper\Cache\FileLocator\DefaultFileLocator;
use Goat\Mapper\Cache\FileLocator\FileLocator;
use Goat\Mapper\Cache\GeneratorStrategy\FileWriterGeneratorStrategy;
use Goat\Mapper\Cache\GeneratorStrategy\GeneratorStrategy;
use Goat\Mapper\Cache\Inflector\ClassNameInflector;
use Goat\Mapper\Cache\Inflector\DefaultClassNameInflector;

class GeneratorConfiguration
{
    private string $generatedClassDirectory;
    private ClassNameInflector $classNameInflector;
    private FileLocator $fileLocator;
    private GeneratorStrategy $generatorStrategy;

    public function setGeneratedClassDirectory(string $generatedClassDirectory): void
    {
        $this->generatedClassDirectory = $generatedClassDirectory;
    }

    public function getGeneratedClassDirectory(): string
    {
        return $this->generatedClassDirectory ?? (
            $this->generatedClassDirectory = \sys_get_temp_dir()
        );
    }

    public function setClassNameInflector(ClassNameInflector $classNameInflector): void
    {
        $this->classNameInflector = $classNameInflector;
    }

    public function getClassNameInflector(): ClassNameInflector
    {
        return $this->classNameInflector ?? (
            $this->classNameInflector = new DefaultClassNameInflector()
        );
    }

    public function setFileLocator(FileLocator $fileLocator): void
    {
        $this->fileLocator = $fileLocator;
    }

    public function getFileLocator(): FileLocator
    {
        return $this->fileLocator ?? (
            $this->fileLocator = new DefaultFileLocator(
                $this->getGeneratedClassDirectory()
            )
        );
    }

    public function setGeneratorStrategy(GeneratorStrategy $generatorStrategy): void
    {
        $this->generatorStrategy = $generatorStrategy;
    }

    public function getGeneratorStrategy(): GeneratorStrategy
    {
        return $this->generatorStrategy ?? (
            $this->generatorStrategy = new FileWriterGeneratorStrategy(
                $this->getFileLocator()
            )
        );
    }
}
