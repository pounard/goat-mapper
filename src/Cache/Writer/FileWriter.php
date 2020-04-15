<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Writer;

final class FileWriter extends AbstractWriter
{
    public function __construct(string $filename)
    {
        if (\file_exists($filename)) {
            if (!@\unlink($filename)) {
                throw new \RuntimeException(\sprintf("'%s': can not delete file"));
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
