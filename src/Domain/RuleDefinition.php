<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;

final readonly class RuleDefinition
{
    /**
     * @param list<self> $children
     * @param list<self>|null $eachRules
     */
    private function __construct(
        private string $field,
        private array $children,
        private ?array $eachRules,
        private bool $eachStrings,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        $keys = array_keys($definition);
        if (!in_array('field', $keys, true) || array_diff($keys, ['field', 'children', 'each']) !== []) {
            throw new InvalidProfileDefinition('rule keys must contain exactly field, optionally with children or each');
        }

        if (!is_string($definition['field']) || $definition['field'] === '') {
            throw new InvalidProfileDefinition('field must be a non-empty string');
        }

        return self::withTraversal($definition);
    }

    public function field(): string
    {
        return $this->field;
    }

    /** @return list<self> */
    public function children(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    /** @return list<self>|null */
    public function eachRules(): ?array
    {
        return $this->eachRules;
    }

    public function eachStrings(): bool
    {
        return $this->eachStrings;
    }

    /** @param array<string, mixed> $definition */
    private static function withTraversal(array $definition): self
    {
        /** @var string $field */
        $field = $definition['field'];
        $hasChildren = array_key_exists('children', $definition);
        $hasEach = array_key_exists('each', $definition);
        if ($hasChildren && $hasEach) {
            throw new InvalidProfileDefinition('a rule cannot contain both children and each');
        }

        if ($hasChildren) {
            self::assertRuleList($definition['children'], 'children must be a non-empty list of rules');
            return new self($field, self::compileChildren($definition['children']), null, false);
        }

        if ($hasEach && $definition['each'] === true) {
            return new self($field, [], null, true);
        }

        if ($hasEach) {
            self::assertRuleList($definition['each'], 'each must be true or a non-empty list of rules');
            return new self($field, [], self::compileChildren($definition['each']), false);
        }

        return new self($field, [], null, false);
    }

    /** @phpstan-assert non-empty-list<mixed> $value */
    private static function assertRuleList(mixed $value, string $message): void
    {
        if (!is_array($value) || !array_is_list($value) || $value === []) {
            throw new InvalidProfileDefinition($message);
        }
    }

    /**
     * @param list<mixed> $definitions
     * @return list<self>
     */
    private static function compileChildren(array $definitions): array
    {
        $rules = [];
        $fields = [];
        foreach ($definitions as $index => $definition) {
            if (!is_array($definition)) {
                throw new InvalidProfileDefinition(sprintf('nested rule %s must be a map', $index));
            }

            /** @var array<string, mixed> $definition */
            $rule = self::fromArray($definition);
            if (isset($fields[$rule->field()])) {
                throw new InvalidProfileDefinition(sprintf('nested rule %s duplicates field "%s"', $index, $rule->field()));
            }

            $fields[$rule->field()] = true;
            $rules[] = $rule;
        }

        return $rules;
    }
}
