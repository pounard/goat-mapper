<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Writer;

interface Writer
{
    const INDENT_SIZE = 4;

    /**
     * Force indentation of given lines (one tab = 4 spaces).
     */
    public function indent(string $input, int $tabs, bool $skipFirstList = false): string;

    /**
     * Reset indentation.
     */
    public function indentationReset(int $howMuch = 0): void;

    /**
     * Increment indentation.
     */
    public function indentationInc(int $howMuch = 1): void;

    /**
     * Decrement indentation.
     */
    public function indentationDec(int $howMuch = 1): void;

    /**
     * Append text to generated file.
     */
    public function write(string $string): void;

    /**
     * Write new line.
     */
    public function newline(): void;

    /**
     * Append text to generated file.
     */
    public function writeInline(string $string): void;

    /**
     * Close.
     */
    public function close(): void;
}
