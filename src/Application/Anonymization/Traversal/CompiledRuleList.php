<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

/**
 * @internal immutable lookup tables for one rule list
 */
final readonly class CompiledRuleList
{
    /**
     * @param list<RuleDefinition> $rules
     * @param array<string, RuleDefinition> $exact
     * @param array<string, RuleDefinition> $normalized
     * @param array<int, self> $nested
     */
    private function __construct(
        private array $rules,
        private array $exact,
        private array $normalized,
        private array $nested,
    ) {
    }

    /**
     * @param list<RuleDefinition> $rules
     */
    public static function compile(array $rules, bool $normalizeKeys): self
    {
        $exact = [];
        $normalized = [];
        $nested = [];
        foreach ($rules as $rule) {
            $exact[$rule->field()] = $rule;
            if ($normalizeKeys) {
                $normalized[KeyNormalizer::normalize($rule->field())] ??= $rule;
            }
            if ($rule->hasChildren()) {
                $nested[spl_object_id($rule)] = self::compile($rule->children(), $normalizeKeys);
            } elseif ($rule->eachRules() !== null) {
                $nested[spl_object_id($rule)] = self::compile($rule->eachRules(), $normalizeKeys);
            }
        }

        return new self($rules, $exact, $normalized, $nested);
    }

    public static function empty(): self
    {
        return new self([], [], [], []);
    }

    /**
     * @return list<RuleDefinition>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function resolve(string $field, bool $normalizeKeys): ?RuleDefinition
    {
        return $this->exact[$field] ?? ($normalizeKeys ? ($this->normalized[KeyNormalizer::normalize($field)] ?? null) : null);
    }

    public function nested(RuleDefinition $rule): ?self
    {
        return $this->nested[spl_object_id($rule)] ?? null;
    }
}
