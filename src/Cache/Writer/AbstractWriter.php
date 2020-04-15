<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Writer;

abstract class AbstractWriter implements Writer
{
    /** @var resource */
    protected $handle;
    protected int $indentation = 0;

    /**
     * Constructor
     */
    protected function __construct(/* resource */ $handle)
    {
        if (!$handle || !\is_resource($handle)) {
            throw new \Exception("\$resource parameter must be a resource.");
        }
        $this->handle = $handle;
    }

    /**
     * {@inheritdoc}
     */
    public function indent(string $input, int $tabs, bool $skipFirstList = false): string
    {
        $indentString = \str_repeat(" ", $tabs * self::INDENT_SIZE);
        if ($skipFirstList) {
            return \preg_replace('/[\n\r]+/', "$0".$indentString, $input);
        }
        return $indentString.\preg_replace('/[\n\r]+/', "$0".$indentString, $input);
    }

    /**
     * {@inheritdoc}
     */
    public function indentationReset(int $howMuch = 0): void
    {
        $this->indentation = \abs($howMuch);
    }

    /**
     * {@inheritdoc}
     */
    public function indentationInc(int $howMuch = 1): void
    {
        $this->indentation += \abs($howMuch);
    }

    /**
     * {@inheritdoc}
     */
    public function indentationDec(int $howMuch = 1): void
    {
        $this->indentation = \max([0, $this->indentation - \abs($howMuch)]);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $string): void
    {
        if ($this->indentation) {
            $string = self::indent($string, $this->indentation);
        }
        $this->doWrite($string);
        $this->doWrite("\n");
    }

    /**
     * {@inheritdoc}
     */
    public function newline(): void
    {
        $this->doWrite("\n");
    }

    /**
     * {@inheritdoc}
     */
    public function writeInline(string $string): void
    {
        $this->doWrite($string);
    }

    /**
     * Attempt to properly close internal resource.
     */
    protected function doClose(): void
    {
        if ($this->handle) {
            @\fclose($this->handle);
        }
        $this->handle = null;
    }

    /**
     * Append text to generated file.
     */
    private function doWrite(string $string): void
    {
        if (!$this->handle) {
            throw new \RuntimeException("File was closed");
        }
        \fwrite($this->handle, $string);
    }
}
