<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal;

use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

/**
 * @internal immutable compiled rule tree for one profile
 */
final readonly class CompiledProfile
{
    public CompiledRuleList $rules;

    public CompiledRuleList $globalRules;

    public CompiledRuleList $emptyRules;

    public function __construct(ProfileDefinition $profile)
    {
        $normalizeKeys = $profile->keyNormalization();
        $this->rules = CompiledRuleList::compile($profile->rules(), $normalizeKeys);
        $this->globalRules = CompiledRuleList::compile($profile->globalRules(), $normalizeKeys);
        $this->emptyRules = CompiledRuleList::empty();
    }
}
