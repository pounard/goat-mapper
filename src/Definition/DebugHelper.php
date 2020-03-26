<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

/**
 * @codeCoverageIgnore
 *   Sorry for this one, this is unnecessary for now.
 */
final class DebugHelper
{
    public static function arrayToString(array $values): string
    {
        return '('.\implode(
            ',',
            \array_map(
                [__CLASS__, 'valueToString'],
                $values
            )
        ).')';
    }

    public static function escape(string $value): string
    {
        // @todo if cli, escape dangerous chars
        return $value;
    }

    public static function quote(string $value, string $quoteChar = "'"): string
    {
        return $quoteChar.$value.$quoteChar;
    }

    public static function valueToString($value): string
    {
        if (null === $value) {
            return 'null';
        }

        if (\is_object($value) && \method_exists($value, '__toString')) {
            $value = (string)$value;
        }

        if (\is_string($value)) {
            if (\strlen($value) > 16) {
                return self::quote(\substr($value, 0, 15)."...");
            }
            return self::quote($value);
        }

        if (!\is_string($value)) {
            if (\is_scalar($value) && !\is_array($value)) {
                return (string)$value;
            }
        }

        return self::getType($value);
    }

    public static function getType($value): string
    {
        if (\is_object($value)) {
            return \get_class($value);
        }
        $type = \gettype($value);
        if ('integer' === $type) {
            return 'int';
        }
        if ('double' === $type) {
            return 'float';
        }
        // https://www.php.net/manual/en/function.gettype.php - as of PHP 7.2.0.
        if ('resource (closed)' === $type) {
            return 'resource';
        }
        return $type;
    }
}
