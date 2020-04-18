<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\GeneratorStrategy;

use Goat\Mapper\Cache\FileLocator\FileLocator;
use Goat\Mapper\Cache\Writer\FileWriter;
use Goat\Mapper\Error\ConfigurationError;

class FileWriterGeneratorStrategy implements GeneratorStrategy
{
    private FileLocator $fileLocator;

    public function __construct(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $generatedClassName, string $generatedCode): void
    {
        if (\class_exists($generatedClassName)) {
            return;
        }

        $filename = $this->fileLocator->getFileName($generatedClassName);

        if (!\file_exists($filename)) {
            FileWriter::writeFile($filename, "<?php\n\ndeclare(strict_types=1);\n\n" . $generatedCode);
        }

        include $filename;
        if (!\class_exists($generatedClassName)) {
            throw new ConfigurationError(\sprintf(
                "Could not include existing file %s: %s",
                $filename,
                (\error_get_last() ?? ['message' => "<no message>"])['message']
            ));
        }
    }
}
