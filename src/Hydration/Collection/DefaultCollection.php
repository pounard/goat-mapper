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

    /** @var bool */
    private $forceRewindable = true;

    /**
     * @param iterable<T>|callable<T> $initializer
     */
    public function __construct($initializer, ?int $count = null, bool $forceRewindable = true)
    {
        $this->forceRewindable = $forceRewindable;

        $values = null;
        if (\is_iterable($initializer)) {
            if ($this->forceRewindable) {
                $values = $this->makeItRewindable($initializer);
            } else {
                $values = $initializer;
            }
        } else if (\is_callable($initializer)) {
            $this->initializer = $initializer;
        } else {
            throw new \InvalidArgumentException("\$initializer argument must be iterable or callable.");
        }

        parent::__construct($values, $count);
    }

    private function makeItRewindable(iterable $values): iterable
    {
        // Poor's man rewindable maker.
        // @todo There's probably a much smarter way to do this.
        if (!\is_array($values)) {
            return \iterator_to_array($values);
        }
        return $values;
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

            $values = $ret->getValues();
            if ($this->forceRewindable) {
                return $this->makeItRewindable($values);
            }

            return $values;
        }

        if (!\is_iterable($ret)) {
            throw new \InvalidArgumentException("\$initializer argument did not return an iterable");
        }

        return $ret;
    }
}
