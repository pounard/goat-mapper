<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Writer;

use Goat\Mapper\Error\ConfigurationError;

final class FileWriter extends AbstractWriter
{
    public static function writeFile(string $filename, string $content): void
    {
        $writer = new self($filename);
        $writer->write($content);
        $writer->close();
    }

    public function __construct(string $filename)
    {
        if (\file_exists($filename)) {
            if (!@\unlink($filename)) {
                throw new \RuntimeException(\sprintf("'%s': can not delete file"));
            }
        } else {
            $dirname = \dirname($filename);
            if (!\is_dir($dirname)) {
                if (!\mkdir($dirname, 0755, true)) {
                    throw new ConfigurationError(\sprintf("'%s': could not create directory"));
                }
            } else if (!\is_writable($dirname)) {
                throw new ConfigurationError(\sprintf("'%s': directory is not writable"));
            }
        }

        if (false === ($handle = \fopen($filename, "a+"))) {
            throw new \RuntimeException(\sprintf("'%s': can not open file for writing"));
        }

        parent::__construct($handle);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->doClose();
    }
}
