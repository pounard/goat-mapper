<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

use Goat\Mapper\Error\QueryError;

final class Identifier implements Debuggable
{
    /** @var mixed[] */
    private $values;

    /**
     * @param Column[] $columns
     */
    public function __construct(array $values)
    {
        $this->values = \array_values($values);
    }

    public static function normalize($input): self
    {
        if ($input instanceof self) {
            return $input;
        }
        if (\is_array($input)) {
            return new self($input);
        }
        if (\is_iterable($input)) {
            return new self(\iterator_to_array($input));
        }
        return new self([$input]);
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function isCompatible(Key $key): bool
    {
        // @todo Check values types?
        return $key->count() === \count($this->values);
    }

    public function failIfNotCompatible(Key $key): void
    {
        if (!$this->isCompatible($key)) {
            throw new QueryError(\sprintf(
                "Identifier %s is not compatible with key %s",
                $this->toString(),
                $key->toString()
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return DebugHelper::arrayToString($this->values);
    }
}
