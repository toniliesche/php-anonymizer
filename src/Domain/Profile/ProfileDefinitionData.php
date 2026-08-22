<?php

declare(strict_types=1);

/** @codeCoverageIgnoreFile Internal parser value. */

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

/**
 * @internal parsed profile construction data
 *
 * @codeCoverageIgnore Internal parser value.
 */
final readonly class ProfileDefinitionData
{
    /**
     * @param list<RuleDefinition> $rules
     * @param list<RuleDefinition> $globalRules
     * @param list<string> $fragmentNames
     */
    public function __construct(
        public string $name,
        public array $rules,
        public ProfileMode $mode,
        public ProfileRoot $root,
        public bool $keyNormalization,
        public array $globalRules,
        public ?ProfileMetadata $metadata,
        public array $fragmentNames,
    ) {
    }
}
