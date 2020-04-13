<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

use Goat\Mapper\Error\QueryError;

final class Identifier implements Debuggable
{
    private int $length;
    private ?string $hash = null;
    private array $values;

    /**
     * @param Column[] $columns
     */
    public function __construct(array $values)
    {
        $this->values = \array_values($values);
        $this->length = \count($values);
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

    private function computeHash(): string
    {
        if (!$this->length) {
            return '';
        }

        // Prey for all values to be Stringable.
        return \implode('\\', $this->values);
    }

    public function getHash(): string
    {
        return $this->hash ?? (
            $this->hash = $this->computeHash()
        );
    }

    public function equals(Identifier $other): bool
    {
        if ($this->length !== $other->length) {
            return false;
        }

        // Previous algorithm was testing each key one by one, sadly some
        // values could be something else than scalars, and the === check
        // would yield false negatives since objects references can differ
        // yet yield the same values at the same time.
        return $this->getHash() === $other->getHash();
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return DebugHelper::arrayToString($this->values);
    }
}
