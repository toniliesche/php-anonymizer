<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule\Condition;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use Webmozart\Assert\Assert;
use function Safe\preg_match;

final readonly class ConditionDefinition
{
    /**
     * @param list<string> $path
     * @param list<null|bool|float|int|string> $expected
     */
    private function __construct(
        private array $path,
        private string $operator,
        private array $expected,
    ) {
    }

    /**
     * @param array<string, mixed> $definition
     */
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

        Assert::isList($path);

        $operator = $operators[0];
        $value = $definition[$operator];
        if ($operator === 'equals') {
            if (!self::isScalarValue($value)) {
                throw new InvalidProfileDefinition('condition equals value must be scalar or null');
            }

            /** @var null|bool|float|int|string $value */
            /** @var list<null|bool|float|int|string> $expected */
            $expected = [$value];

            return new self($path, $operator, $expected);
        }

        if (!is_array($value) || !array_is_list($value) || $value === []) {
            throw new InvalidProfileDefinition('condition in value must be a non-empty list');
        }

        /** @var list<null|bool|float|int|string> $expected */
        $expected = [];
        foreach ($value as $item) {
            if (!self::isScalarValue($item)) {
                throw new InvalidProfileDefinition('condition in values must be scalar or null');
            }

            /** @var null|bool|float|int|string $item */
            $expected[] = $item;
        }

        Assert::isList($path);

        return new self($path, $operator, $expected);
    }

    /**
     * @param array<mixed, mixed> $context
     */
    public function matches(array $context, bool $normalizeKeys = false): bool
    {
        $value = $this->lookup($context, $normalizeKeys);
        if (!self::isScalarValue($value)) {
            return false;
        }

        return $this->operator === 'equals' ? $value === $this->expected[0] : array_any($this->expected, static fn (bool|float|int|string|null $expected): bool => $value === $expected);
    }

    /**
     * @return list<string>
     */
    public function path(): array
    {
        return $this->path;
    }

    /**
     * @param array<mixed, mixed> $context
     */
    private function lookup(array $context, bool $normalizeKeys): mixed
    {
        $value = $context;
        foreach ($this->path as $segment) {
            [$found, $value] = $this->segment($value, $segment, $normalizeKeys);
            if (!$found) {
                return null;
            }
        }

        return $value;
    }

    /**
     * @return array{0: bool, 1: mixed}
     */
    private function segment(mixed $value, string $segment, bool $normalizeKeys): array
    {
        if (!is_array($value) || (array_is_list($value) && $value !== [])) {
            return [false, null];
        }
        if (array_key_exists($segment, $value)) {
            return [true, $value[$segment]];
        }
        if (!$normalizeKeys) {
            return [false, null];
        }
        $normalized = KeyNormalizer::normalize($segment);
        foreach ($value as $key => $candidate) {
            if (KeyNormalizer::normalize((string) $key) === $normalized) {
                return [true, $candidate];
            }
        }

        return [false, null];
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

    private static function isScalarValue(mixed $value): bool
    {
        return $value === null || is_bool($value) || is_float($value) || is_int($value) || is_string($value);
    }
}
