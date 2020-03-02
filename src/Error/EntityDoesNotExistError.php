<?php

declare(strict_types=1);

namespace Goat\Mapper\Error;

class EntityDoesNotExistError extends \RuntimeException implements MapperError
{
}
