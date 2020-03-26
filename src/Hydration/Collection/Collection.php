<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\Collection;

/**
 * Object collections will be as opened as possible.
 *
 * \ArrayAccess interface is here for convenance, nevertheless it would be
 * best for performances not to use it, further you cannot predict what wil
 * the keys look like.
 */
interface Collection extends \Countable, \Traversable, \ArrayAccess
{
}
