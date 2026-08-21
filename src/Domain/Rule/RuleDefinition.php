<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;

final readonly class RuleDefinition
{
    /**
     * @param list<self> $children
     * @param null|list<self> $eachRules
     */
    private function __construct(
        private string $field,
        private array $children,
        private ?array $eachRules,
        private bool $eachStrings,
        private ?ConditionDefinition $condition,
        private ?ScalarAction $scalarAction,
        private ?string $strategy,
        private ?string $keyId,
        private ?EmbeddedPayloadDefinition $embedded,
        private ?TransformationDefinition $transformation,
    ) {
    }

    /**
     * @param array<string, mixed> $definition
     */
    public static function fromArray(array $definition): self
    {
        $keys = array_keys($definition);
        if (!in_array('field', $keys, true) || array_diff($keys, ['field', 'children', 'each', 'strategy', 'key_id', 'when', 'embedded', 'transform', 'on_scalar']) !== []) {
            throw new InvalidProfileDefinition('rule keys must contain field, optionally with children, each, strategy, key_id, when, embedded, transform, or on_scalar');
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

    /**
     * @return list<self>
     */
    public function children(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    /**
     * @return null|list<self>
     */
    public function eachRules(): ?array
    {
        return $this->eachRules;
    }

    public function eachStrings(): bool
    {
        return $this->eachStrings;
    }

    public function condition(): ?ConditionDefinition
    {
        return $this->condition;
    }

    public function scalarAction(): ?ScalarAction
    {
        return $this->scalarAction;
    }

    public function strategy(): ?string
    {
        return $this->strategy;
    }

    public function keyId(): ?string
    {
        return $this->keyId;
    }

    public function embedded(): ?EmbeddedPayloadDefinition
    {
        return $this->embedded;
    }

    /**
     * @codeCoverageIgnore
     */
    public function transformation(): ?TransformationDefinition
    {
        return $this->transformation;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private static function withTraversal(array $definition): self
    {
        /** @var string $field */
        $field = $definition['field'];
        $hasChildren = array_key_exists('children', $definition);
        $hasEach = array_key_exists('each', $definition);
        $hasStrategy = array_key_exists('strategy', $definition);
        $hasEmbedded = array_key_exists('embedded', $definition);
        $hasTransform = array_key_exists('transform', $definition);
        self::assertTraversalCombination($hasChildren, $hasEach, $hasStrategy, $hasEmbedded, $hasTransform);

        if ($hasChildren) {
            self::assertRuleList($definition['children'], 'children must be a non-empty list of rules');

            return new self($field, self::compileChildren($definition['children']), null, false, RuleOptionsCompiler::condition($definition), RuleOptionsCompiler::scalarAction($definition, true), null, null, null, null);
        }

        if ($hasEach && $definition['each'] === true) {
            return new self($field, [], null, true, RuleOptionsCompiler::condition($definition), RuleOptionsCompiler::scalarAction($definition, true), null, null, null, null);
        }

        if ($hasEach) {
            self::assertRuleList($definition['each'], 'each must be true or a non-empty list of rules');

            return new self($field, [], self::compileChildren($definition['each']), false, RuleOptionsCompiler::condition($definition), RuleOptionsCompiler::scalarAction($definition, true), null, null, null, null);
        }

        if (array_key_exists('on_scalar', $definition)) {
            throw new InvalidProfileDefinition('on_scalar requires children or each');
        }

        return new self($field, [], null, false, RuleOptionsCompiler::condition($definition), null, RuleOptionsCompiler::strategy($definition), RuleOptionsCompiler::keyId($definition), RuleOptionsCompiler::embedded($definition), RuleOptionsCompiler::transformation($definition));
    }

    /**
     * @codeCoverageIgnore
     */
    private static function assertTraversalCombination(bool $hasChildren, bool $hasEach, bool $hasStrategy, bool $hasEmbedded, bool $hasTransform): void
    {
        if ($hasChildren && $hasEach) {
            throw new InvalidProfileDefinition('a rule cannot contain both children and each');
        }
        if (($hasChildren || $hasEach) && ($hasStrategy || $hasEmbedded || $hasTransform)) {
            throw new InvalidProfileDefinition('strategy, transform, and embedded require a leaf rule');
        }
        if ($hasStrategy && $hasEmbedded) {
            throw new InvalidProfileDefinition('a rule cannot contain both strategy and embedded');
        }
        if (($hasStrategy && $hasTransform) || ($hasEmbedded && $hasTransform)) {
            throw new InvalidProfileDefinition('a rule cannot contain more than one of strategy, transform, or embedded');
        }
    }

    /**
     * @phpstan-assert non-empty-list<mixed> $value
     */
    private static function assertRuleList(mixed $value, string $message): void
    {
        if (!is_array($value) || !array_is_list($value) || $value === []) {
            throw new InvalidProfileDefinition($message);
        }
    }

    /**
     * @param list<mixed> $definitions
     *
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
