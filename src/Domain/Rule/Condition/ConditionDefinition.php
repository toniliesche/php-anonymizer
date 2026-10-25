<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule\Condition;

use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;

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
        $parsed = ConditionDefinitionParser::parse($definition);

        return new self($parsed['path'], $parsed['operator'], $parsed['expected']);
    }

    /**
     * @param array<mixed, mixed> $context
     */
    public function matches(array $context, bool $normalizeKeys = false): bool
    {
        [$found, $value] = $this->lookup($context, $normalizeKeys);
        if (!$found || !ConditionDefinitionParser::isScalarValue($value)) {
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
     *
     * @return array{0: bool, 1: mixed}
     */
    private function lookup(array $context, bool $normalizeKeys): array
    {
        $value = $context;
        foreach ($this->path as $segment) {
            [$found, $value] = $this->segment($value, $segment, $normalizeKeys);
            if (!$found) {
                return [false, null];
            }
        }

        return [true, $value];
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
}
