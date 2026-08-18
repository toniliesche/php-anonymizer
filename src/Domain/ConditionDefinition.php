<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;

use function Safe\preg_match;

final readonly class ConditionDefinition
{
    /**
     * @param list<string> $path
     * @param list<bool|float|int|string|null> $expected
     */
    private function __construct(
        private array $path,
        private string $operator,
        private array $expected,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        if (!array_key_exists('path', $definition) || !is_string($definition['path'])) {
            throw new InvalidProfileDefinition('condition path must be a non-empty relative path');
        }

        $path = self::parsePath($definition['path']);
        $operators = array_values(array_intersect(['equals', 'in'], array_keys($definition)));
        if (count($operators) !== 1 || array_diff(array_keys($definition), ['path', ...$operators]) !== []) {
            throw new InvalidProfileDefinition('condition must contain path and exactly one of equals or in');
        }

        $operator = $operators[0];
        $value = $definition[$operator];
        if ($operator === 'equals') {
            if (!self::isScalarValue($value)) {
                throw new InvalidProfileDefinition('condition equals value must be scalar or null');
            }

            /** @var list<string> $path */
            /** @var bool|float|int|string|null $value */
            /** @var list<bool|float|int|string|null> $expected */
            $expected = [$value];
            return new self($path, $operator, $expected);
        }

        if (!is_array($value) || !array_is_list($value) || $value === []) {
            throw new InvalidProfileDefinition('condition in value must be a non-empty list');
        }

        /** @var list<bool|float|int|string|null> $expected */
        $expected = [];
        foreach ($value as $item) {
            if (!self::isScalarValue($item)) {
                throw new InvalidProfileDefinition('condition in values must be scalar or null');
            }
            /** @var bool|float|int|string|null $item */
            $expected[] = $item;
        }

        /** @var list<string> $path */
        return new self($path, $operator, $expected);
    }

    /** @param array<mixed, mixed> $context */
    public function matches(array $context): bool
    {
        $value = $context;
        foreach ($this->path as $segment) {
            if (!is_array($value) || (array_is_list($value) && $value !== []) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        if (!self::isScalarValue($value)) {
            return false;
        }

        if ($this->operator === 'equals') {
            return $value === $this->expected[0];
        }
        return array_any($this->expected, fn(bool|float|int|string|null $expected): bool => $value === $expected);
    }

    /** @return list<string> */
    public function path(): array
    {
        return $this->path;
    }

    /** @return list<string> */
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

    private static function isScalarValue(mixed $value): bool
    {
        return $value === null || is_bool($value) || is_float($value) || is_int($value) || is_string($value);
    }
}
