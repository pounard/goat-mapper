<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\Collection;

/**
 * @var Collection<T>
 */
final class DefaultCollection extends AbstractCollection
{
    /** @var null|callable */
    private $initializer = null;

    /**
     * @param iterable<T>|callable<T> $initializer
     */
    public function __construct($initializer, ?int $count = null)
    {
        $values = null;

        if (\is_iterable($initializer)) {
            $values = $initializer;
        } else if (\is_callable($initializer)) {
            $this->initializer = $initializer;
        } else {
            throw new \InvalidArgumentException("\$initializer argument must be iterable or callable.");
        }

        parent::__construct($values, $count);
    }

    /**
     * Load data, and return an array.
     */
    protected function doInitialize(): iterable
    {
        if (!$this->initializer) {
            throw new \InvalidArgumentException("missing \$initializer, invalid object state");
        }

        $ret = \call_user_func($this->initializer);

        if ($ret instanceof CollectionInitializerResult) {
            if (null !== ($count = $ret->getCount())) {
                $this->setCount($count);
            }

            return $ret->getValues();
        }

        if (!\is_iterable($ret)) {
            throw new \InvalidArgumentException("\$initializer argument did not return an iterable");
        }

        return $ret;
    }
}
