<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use function Safe\preg_match;

// @phpstan-ignore complexity.classLike (profile validation branches are explicit)
final readonly class ProfileDefinition
{
    /**
     * @param list<RuleDefinition> $rules
     * @param list<RuleDefinition> $globalRules
     * @param list<string> $fragmentNames
     */
    private function __construct(
        private string $name,
        private array $rules,
        private ProfileMode $mode,
        private ProfileRoot $root,
        private bool $keyNormalization,
        private array $globalRules,
        private ?ProfileMetadata $metadata,
        private array $fragmentNames,
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
        if (!in_array('mode', $keys, true) || array_diff($keys, ['mode', 'root', 'rules', 'key_normalization', 'global_rules', 'metadata', 'fragments']) !== []) {
            throw InvalidProfileDefinition::forProfile($name, 'keys must be exactly mode and rules, with optional root, key_normalization, global_rules, metadata, and fragments');
        }

        $mode = ProfileMode::tryFrom(is_string($definition['mode'] ?? null) ? $definition['mode'] : '');
        if (!$mode instanceof ProfileMode) {
            throw InvalidProfileDefinition::forProfile($name, 'mode must be redact or preserve');
        }

        $rules = [];
        if (array_key_exists('rules', $definition)) {
            if (!is_array($definition['rules']) || !array_is_list($definition['rules']) || $definition['rules'] === []) {
                throw InvalidProfileDefinition::forProfile($name, 'rules must be a non-empty list when provided');
            }
        }
        if (array_key_exists('global_rules', $definition) && (!is_array($definition['global_rules']) || !array_is_list($definition['global_rules']) || $definition['global_rules'] === [])) {
            throw InvalidProfileDefinition::forProfile($name, 'global_rules must be a non-empty list when provided');
        }
        if (!array_key_exists('rules', $definition) && !array_key_exists('global_rules', $definition)) {
            throw InvalidProfileDefinition::forProfile($name, 'keys must be exactly mode and rules, with optional root, key_normalization, global_rules, metadata, and fragments; at least one selector must be provided');
        }
        if (array_key_exists('key_normalization', $definition) && !is_bool($definition['key_normalization'])) {
            throw InvalidProfileDefinition::forProfile($name, 'key_normalization must be a boolean');
        }

        $metadata = null;
        if (array_key_exists('metadata', $definition)) {
            if (!is_array($definition['metadata']) || $definition['metadata'] === [] || array_is_list($definition['metadata'])) {
                throw InvalidProfileDefinition::forProfile($name, 'metadata must be a non-empty map when provided');
            }

            $metadataKeys = array_keys($definition['metadata']);
            if (array_diff($metadataKeys, ['schema_version', 'profile_version', 'owner', 'status', 'purpose']) !== []) {
                throw InvalidProfileDefinition::forProfile($name, 'metadata contains an unknown key');
            }

            /** @var non-empty-array<string, mixed> $metadataDefinition */
            $metadataDefinition = $definition['metadata'];
            foreach ($metadataDefinition as $metadataKey => $metadataValue) {
                if (!is_string($metadataValue) || trim($metadataValue) === '') {
                    throw InvalidProfileDefinition::forProfile($name, sprintf('metadata.%s must be a non-empty string', $metadataKey));
                }
            }

            try {
                $metadata = new ProfileMetadata(
                    self::metadataValue($metadataDefinition, 'schema_version'),
                    self::metadataValue($metadataDefinition, 'profile_version'),
                    self::metadataValue($metadataDefinition, 'owner'),
                    self::metadataValue($metadataDefinition, 'status'),
                    self::metadataValue($metadataDefinition, 'purpose'),
                );
            } catch (InvalidArgumentException $exception) {
                throw InvalidProfileDefinition::at($name, $exception->getMessage());
            }
        }

        $root = ProfileRoot::tryFrom(is_string($definition['root'] ?? null) ? $definition['root'] : 'map');
        if (!$root instanceof ProfileRoot) {
            throw InvalidProfileDefinition::forProfile($name, 'root must be map or list');
        }

        $fields = [];

        $composition = new ProfileCompositionCompiler()->compile($name, $definition);

        /** @var list<array<string, mixed>> $ruleDefinitions */
        $ruleDefinitions = $composition['rules'];
        foreach ($ruleDefinitions as $index => $rule) {
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

        $globalRules = [];
        $globalFields = [];

        /** @var list<mixed> $globalRuleDefinitions */
        $globalRuleDefinitions = $definition['global_rules'] ?? [];
        foreach ($globalRuleDefinitions as $index => $rule) {
            if (!is_array($rule)) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s] must be a map', $index));
            }

            /** @var array<string, mixed> $rule */
            try {
                $ruleDefinition = RuleDefinition::fromGlobalArray($rule);
            } catch (InvalidProfileDefinition $exception) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s]: %s', $index, $exception->getMessage()));
            }

            if (isset($globalFields[$ruleDefinition->field()])) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s]: field "%s" is duplicated', $index, $ruleDefinition->field()));
            }

            $globalFields[$ruleDefinition->field()] = true;
            $globalRules[] = $ruleDefinition;
        }

        return new self($name, $rules, $mode, $root, $definition['key_normalization'] ?? false, $globalRules, $metadata, $composition['fragments']);
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

    public function keyNormalization(): bool
    {
        return $this->keyNormalization;
    }

    /**
     * @return list<RuleDefinition>
     */
    public function globalRules(): array
    {
        return $this->globalRules;
    }

    public function metadata(): ?ProfileMetadata
    {
        return $this->metadata;
    }

    /**
     * @return list<string>
     */
    public function fragmentNames(): array
    {
        return $this->fragmentNames;
    }

    public function fingerprint(): string
    {
        return hash('sha256', serialize([
            $this->name,
            $this->mode,
            $this->root,
            $this->keyNormalization,
            $this->rules,
            $this->globalRules,
        ]));
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @codeCoverageIgnore
     */
    private static function metadataValue(array $metadata, string $key): ?string
    {
        if (!array_key_exists($key, $metadata)) {
            return null;
        }
        $value = $metadata[$key];
        if (!is_string($value)) {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(sprintf('metadata.%s must be a string', $key));
            // @codeCoverageIgnoreEnd
        }

        return $value;
    }
}
