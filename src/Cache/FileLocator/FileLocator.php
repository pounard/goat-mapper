<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\FileLocator;

interface FileLocator
{
    /**
     * Retrieves the file name for the given generated class.
     */
    public function getFileName(string $generatedClassName): string;
}
