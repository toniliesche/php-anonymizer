<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

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
    public static function fromArray(string $name, array $definition): self
    {
        $data = new ProfileDefinitionParser()->parse($name, $definition);

        return new self($data->name, $data->rules, $data->mode, $data->root, $data->keyNormalization, $data->globalRules, $data->metadata, $data->fragmentNames);
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
        return hash('sha256', serialize([$this->name, $this->mode, $this->root, $this->keyNormalization, $this->rules, $this->globalRules]));
    }
}
