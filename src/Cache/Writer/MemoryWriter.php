<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Writer;

/**
 * In memory temporary writer.
 */
final class MemoryWriter extends AbstractWriter
{
    public function __construct()
    {
        parent::__construct(\fopen('php://memory', 'w'));
    }

    /**
     * Get current buffer.
     */
    public function closeAndGetBuffer(): string
    {
        if (!$this->handle) {
            throw new \RuntimeException("File was closed");
        }
        \rewind($this->handle);
        try {
            return \stream_get_contents($this->handle);
        } finally {
            $this->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->doClose();
    }
}
