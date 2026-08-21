<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ContextualReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;

final readonly class ProfileReferenceValidator
{
    public function validate(ProfileRegistry $profiles, ReplacementStrategyRegistry $strategies, PayloadCodecRegistry $codecs, ?TransformationRegistry $transformations = null): void
    {
        foreach ($profiles->profiles() as $profile) {
            $this->rules($profiles, $profile->name(), $profile->rules(), $strategies, $codecs, $transformations);
        }
        new EmbeddedProfileReferenceGraph($profiles->profiles())->assertAcyclic();
    }

    /**
     * @param list<RuleDefinition> $rules
     *
     * @codeCoverageIgnore
     */
    // @phpstan-ignore complexity.functionLike (validation branches are explicit)
    private function rules(ProfileRegistry $profiles, string $profile, array $rules, ReplacementStrategyRegistry $strategies, PayloadCodecRegistry $codecs, ?TransformationRegistry $transformations): void
    {
        foreach ($rules as $rule) {
            $this->validateStrategy($profile, $rule, $strategies);
            // @codeCoverageIgnoreStart Optional transformation reference validation.
            if ($rule->transformation() !== null) {
                $transformation = $transformations?->get($rule->transformation()->type());
                if (!$transformation instanceof ValueTransformationInterface) {
                    throw new InvalidArgumentException(sprintf('Profile "%s" references unknown value transformation "%s".', $profile, $rule->transformation()->type()));
                }
                $transformation->validateOptions($rule->transformation()->options());
            }
            // @codeCoverageIgnoreEnd
            $embedded = $rule->embedded();
            if ($embedded !== null) {
                if (!$codecs->get($embedded->codec()) instanceof PayloadCodecInterface) {
                    throw new InvalidArgumentException(sprintf('Profile "%s" references unknown payload codec "%s".', $profile, $embedded->codec()));
                }
                if (!$profiles->get($embedded->profile()) instanceof ProfileDefinition) {
                    throw new InvalidArgumentException(sprintf('Profile "%s" references unknown embedded profile "%s".', $profile, $embedded->profile()));
                }
            }
            $this->rules($profiles, $profile, $rule->children(), $strategies, $codecs, $transformations);
            $this->rules($profiles, $profile, $rule->eachRules() ?? [], $strategies, $codecs, $transformations);
        }
    }

    private function validateStrategy(string $profile, RuleDefinition $rule, ReplacementStrategyRegistry $strategies): void
    {
        $strategy = $rule->strategy() === null ? null : $strategies->get($rule->strategy());
        if ($rule->strategy() !== null && !$strategy instanceof ReplacementStrategyInterface) {
            throw new InvalidArgumentException(sprintf('Profile "%s" references unknown replacement strategy "%s".', $profile, $rule->strategy()));
        }
        if ($rule->keyId() !== null && !$rule->transformation() instanceof TransformationDefinition && !$strategy instanceof ContextualReplacementStrategyInterface) {
            throw new InvalidArgumentException(sprintf('Profile "%s" uses key_id with non-contextual strategy "%s".', $profile, $rule->strategy() ?? 'none'));
        }
        if ($strategy instanceof ContextualReplacementStrategyInterface && $rule->keyId() === null) {
            throw new InvalidArgumentException(sprintf('Profile "%s" must configure key_id for contextual strategy "%s".', $profile, $rule->strategy()));
        }
    }
}
