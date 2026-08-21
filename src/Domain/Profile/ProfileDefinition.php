<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use function Safe\preg_match;

final readonly class ProfileDefinition
{
    /**
     * @param list<RuleDefinition> $rules
     */
    private function __construct(
        private string $name,
        private array $rules,
        private ProfileMode $mode,
        private ProfileRoot $root,
    ) {
    }

    /**
     * @param array<string, mixed> $definition
     */
    // @phpstan-ignore complexity.functionLike (profile validation branches are explicit)
    public static function fromArray(string $name, array $definition): self
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $name) !== 1) {
            throw InvalidProfileDefinition::forProfile($name, 'the name is invalid');
        }

        $keys = array_keys($definition);
        sort($keys);
        if (!in_array($keys, [['mode', 'rules'], ['mode', 'root', 'rules']], true)) {
            throw InvalidProfileDefinition::forProfile($name, 'keys must be exactly mode and rules, with optional root');
        }

        $mode = ProfileMode::tryFrom(is_string($definition['mode'] ?? null) ? $definition['mode'] : '');
        if (!$mode instanceof ProfileMode) {
            throw InvalidProfileDefinition::forProfile($name, 'mode must be redact or preserve');
        }

        if (!array_key_exists('rules', $definition) || !is_array($definition['rules']) || array_is_list($definition['rules']) === false || $definition['rules'] === []) {
            throw InvalidProfileDefinition::forProfile($name, 'rules must be a non-empty list');
        }

        $root = ProfileRoot::tryFrom(is_string($definition['root'] ?? null) ? $definition['root'] : 'map');
        if (!$root instanceof ProfileRoot) {
            throw InvalidProfileDefinition::forProfile($name, 'root must be map or list');
        }

        $rules = [];
        $fields = [];
        foreach ($definition['rules'] as $index => $rule) {
            if (!is_array($rule)) {
                throw InvalidProfileDefinition::at($name, sprintf('rules[%s] must be a map', $index));
            }

            /** @var array<string, mixed> $rule */
            try {
                $ruleDefinition = RuleDefinition::fromArray($rule);
            } catch (InvalidProfileDefinition $exception) {
                throw InvalidProfileDefinition::at($name, sprintf('rules[%s]: %s', $index, $exception->getMessage()));
            }

            if (isset($fields[$ruleDefinition->field()])) {
                throw InvalidProfileDefinition::at($name, sprintf('rules[%s]: field "%s" is duplicated', $index, $ruleDefinition->field()));
            }

            $fields[$ruleDefinition->field()] = true;
            $rules[] = $ruleDefinition;
        }

        return new self($name, $rules, $mode, $root);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return list<RuleDefinition>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function mode(): ProfileMode
    {
        return $this->mode;
    }

    public function root(): ProfileRoot
    {
        return $this->root;
    }

    public function fingerprint(): string
    {
        return hash('sha256', serialize($this));
    }
}
