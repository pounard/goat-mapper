<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

interface Debuggable
{
    /**
     * Get a string representation of the given object for debugging or
     * informative purpose: this will happen mostly during exception message
     * building; it might be unperformant.
     */
    public function toString(): string;
}
