<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;

use function Safe\preg_match;

final readonly class ProfileDefinition
{
    /** @param list<RuleDefinition> $rules */
    private function __construct(
        private string $name,
        private array $rules,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(string $name, array $definition): self
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $name) !== 1) {
            throw InvalidProfileDefinition::forProfile($name, 'the name is invalid');
        }

        if (array_keys($definition) !== ['mode', 'rules']) {
            throw InvalidProfileDefinition::forProfile($name, 'keys must be exactly mode and rules');
        }

        if (!array_key_exists('mode', $definition) || $definition['mode'] !== 'redact') {
            throw InvalidProfileDefinition::forProfile($name, 'mode must be redact');
        }

        if (!array_key_exists('rules', $definition) || !is_array($definition['rules']) || array_is_list($definition['rules']) === false || $definition['rules'] === []) {
            throw InvalidProfileDefinition::forProfile($name, 'rules must be a non-empty list');
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

        return new self($name, $rules);
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return list<RuleDefinition> */
    public function rules(): array
    {
        return $this->rules;
    }
}
