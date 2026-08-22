<?php

declare(strict_types=1);

/** @codeCoverageIgnoreFile Internal parser collaborator. */

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use function Safe\preg_match;

/**
 * @internal schema, metadata, and rule parser for ProfileDefinition
 *
 * @codeCoverageIgnore Internal parser collaborator.
 */
final class ProfileDefinitionParser
{
    /**
     * @param array<string, mixed> $definition
     */
    public function parse(string $name, array $definition): ProfileDefinitionData
    {
        $this->validateName($name);
        $this->validateKeys($name, $definition);
        $mode = $this->mode($name, $definition);
        $this->validateSelectorLists($name, $definition);
        $metadata = new ProfileMetadataParser()->parse($name, $definition);
        $root = $this->root($name, $definition);
        $composition = new ProfileCompositionCompiler()->compile($name, $definition);
        $rules = $this->rules($name, $composition['rules']);

        /** @var list<mixed> $globalDefinitions */
        $globalDefinitions = $definition['global_rules'] ?? [];
        $globalRules = $this->globalRules($name, $globalDefinitions);

        return new ProfileDefinitionData($name, $rules, $mode, $root, (bool) ($definition['key_normalization'] ?? false), $globalRules, $metadata, $composition['fragments']);
    }

    private function validateName(string $name): void
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $name) !== 1) {
            throw InvalidProfileDefinition::forProfile($name, 'the name is invalid');
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function validateKeys(string $name, array $definition): void
    {
        $keys = array_keys($definition);
        if (!in_array('mode', $keys, true) || array_diff($keys, ['mode', 'root', 'rules', 'key_normalization', 'global_rules', 'metadata', 'fragments']) !== []) {
            throw InvalidProfileDefinition::forProfile($name, 'keys must be exactly mode and rules, with optional root, key_normalization, global_rules, metadata, and fragments');
        }
        if (array_key_exists('key_normalization', $definition) && !is_bool($definition['key_normalization'])) {
            throw InvalidProfileDefinition::forProfile($name, 'key_normalization must be a boolean');
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function mode(string $name, array $definition): ProfileMode
    {
        $mode = ProfileMode::tryFrom(is_string($definition['mode'] ?? null) ? $definition['mode'] : '');
        if (!$mode instanceof ProfileMode) {
            throw InvalidProfileDefinition::forProfile($name, 'mode must be redact or preserve');
        }

        return $mode;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function validateSelectorLists(string $name, array $definition): void
    {
        foreach (['rules', 'global_rules'] as $key) {
            if (array_key_exists($key, $definition) && (!is_array($definition[$key]) || !array_is_list($definition[$key]) || $definition[$key] === [])) {
                throw InvalidProfileDefinition::forProfile($name, sprintf('%s must be a non-empty list when provided', $key));
            }
        }
        if (!array_key_exists('rules', $definition) && !array_key_exists('global_rules', $definition)) {
            throw InvalidProfileDefinition::forProfile($name, 'keys must be exactly mode and rules, with optional root, key_normalization, global_rules, metadata, and fragments; at least one selector must be provided');
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function root(string $name, array $definition): ProfileRoot
    {
        $root = ProfileRoot::tryFrom(is_string($definition['root'] ?? null) ? $definition['root'] : 'map');
        if (!$root instanceof ProfileRoot) {
            throw InvalidProfileDefinition::forProfile($name, 'root must be map or list');
        }

        return $root;
    }

    /** @param list<array<string, mixed>> $definitions
     * @return list<RuleDefinition> */
    private function rules(string $name, array $definitions): array
    {
        $rules = [];
        $fields = [];
        foreach ($definitions as $index => $definition) {
            try {
                $rule = RuleDefinition::fromArray($definition);
            } catch (InvalidProfileDefinition $exception) {
                throw InvalidProfileDefinition::at($name, sprintf('rules[%s]: %s', $index, $exception->getMessage()));
            }
            if (isset($fields[$rule->field()])) {
                throw InvalidProfileDefinition::at($name, sprintf('rules[%s]: field "%s" is duplicated', $index, $rule->field()));
            }
            $fields[$rule->field()] = true;
            $rules[] = $rule;
        }

        return $rules;
    }

    /** @param list<mixed> $definitions
     * @return list<RuleDefinition> */
    private function globalRules(string $name, array $definitions): array
    {
        $rules = [];
        $fields = [];
        foreach ($definitions as $index => $definition) {
            if (!is_array($definition)) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s] must be a map', $index));
            }

            /** @var array<string, mixed> $definition */
            try {
                $rule = RuleDefinition::fromGlobalArray($definition);
            } catch (InvalidProfileDefinition $exception) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s]: %s', $index, $exception->getMessage()));
            }
            if (isset($fields[$rule->field()])) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s]: field "%s" is duplicated', $index, $rule->field()));
            }
            $fields[$rule->field()] = true;
            $rules[] = $rule;
        }

        return $rules;
    }
}
