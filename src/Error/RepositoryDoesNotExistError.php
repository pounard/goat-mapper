<?php

declare(strict_types=1);

namespace Goat\Mapper\Error;

class RepositoryDoesNotExistError extends \RuntimeException implements MapperError
{
}
