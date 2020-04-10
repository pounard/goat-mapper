<?php

declare(strict_types=1);

namespace Goat\Mapper\Error;

/**
 * This error cannot be specialized, otherwise we risk false negatives
 * and hiding errors in LegacyChainDefinitionRegistry.
 */
final class RepositoryDoesNotExistError extends \RuntimeException implements MapperError
{
}
