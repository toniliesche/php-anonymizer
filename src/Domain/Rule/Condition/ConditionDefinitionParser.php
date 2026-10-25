<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule\Condition;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use Webmozart\Assert\Assert;
use function Safe\preg_match;

/**
 * @internal
 */
final class ConditionDefinitionParser
{
    /**
     * @param array<string, mixed> $definition
     *
     * @return array{path: list<string>, operator: 'equals'|'in', expected: list<null|bool|float|int|string>}
     */
    public static function parse(array $definition): array
    {
        $path = self::path($definition);
        $operator = self::operator($definition);
        $expected = self::expected($operator, $definition[$operator]);

        return ['path' => $path, 'operator' => $operator, 'expected' => $expected];
    }

    public static function isScalarValue(mixed $value): bool
    {
        return $value === null || is_bool($value) || is_float($value) || is_int($value) || is_string($value);
    }

    /** @param array<string, mixed> $definition
     * @return list<string> */
    private static function path(array $definition): array
    {
        if (!array_key_exists('path', $definition) || !is_string($definition['path'])) {
            throw new InvalidProfileDefinition('condition path must be a non-empty relative path');
        }

        $path = self::parsePath($definition['path']);
        Assert::isList($path);

        return $path;
    }

    /** @param array<string, mixed> $definition
     * @return 'equals'|'in' */
    private static function operator(array $definition): string
    {
        $operators = array_values(array_intersect(['equals', 'in'], array_keys($definition)));
        if (count($operators) !== 1 || array_diff(array_keys($definition), ['path', ...$operators]) !== []) {
            throw new InvalidProfileDefinition('condition must contain path and exactly one of equals or in');
        }

        return $operators[0];
    }

    /**
     * @return list<null|bool|float|int|string>
     */
    private static function expected(string $operator, mixed $value): array
    {
        if ($operator === 'equals') {
            return [self::scalar($value, 'condition equals value must be scalar or null')];
        }

        if (!is_array($value) || !array_is_list($value) || $value === []) {
            throw new InvalidProfileDefinition('condition in value must be a non-empty list');
        }

        $expected = [];
        foreach ($value as $item) {
            $expected[] = self::scalar($item, 'condition in values must be scalar or null');
        }

        return $expected;
    }

    /**
     * @return list<string>
     */
    private static function parsePath(string $path): array
    {
        if ($path === '' || str_starts_with($path, '/') || str_ends_with($path, '/')) {
            throw new InvalidProfileDefinition('condition path must be a non-empty relative path');
        }

        $segments = explode('/', $path);
        foreach ($segments as $index => $segment) {
            if ($segment === '' || preg_match('/~(?![01])/', $segment) === 1) {
                throw new InvalidProfileDefinition(sprintf('condition path segment %s is invalid', $index));
            }
            $segments[$index] = str_replace(['~1', '~0'], ['/', '~'], $segment);
        }

        return $segments;
    }

    private static function scalar(mixed $value, string $message): null|bool|float|int|string
    {
        if ($value !== null && !is_bool($value) && !is_float($value) && !is_int($value) && !is_string($value)) {
            throw new InvalidProfileDefinition($message);
        }

        return $value;
    }
}
