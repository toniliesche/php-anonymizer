<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;

final readonly class RuleDefinition
{
    private function __construct(private string $field) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        if (array_keys($definition) !== ['field']) {
            throw new InvalidProfileDefinition('rule keys must contain exactly field');
        }

        if (!is_string($definition['field']) || $definition['field'] === '') {
            throw new InvalidProfileDefinition('field must be a non-empty string');
        }

        return new self($definition['field']);
    }

    public function field(): string
    {
        return $this->field;
    }
}
