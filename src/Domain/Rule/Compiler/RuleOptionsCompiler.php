<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule\Compiler;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

final class RuleOptionsCompiler
{
    /** @param array<string, mixed> $definition */
    public static function condition(array $definition): ?ConditionDefinition
    {
        if (!array_key_exists('when', $definition)) {
            return null;
        }
        if (!is_array($definition['when'])) {
            throw new InvalidProfileDefinition('when must be a map');
        }

        /** @var array<string, mixed> $condition */
        $condition = $definition['when'];
        return ConditionDefinition::fromArray($condition);
    }

    /** @param array<string, mixed> $definition */
    public static function scalarAction(array $definition, bool $hasTraversal): ?ScalarAction
    {
        if (!array_key_exists('on_scalar', $definition)) {
            return null;
        }
        $action = ScalarAction::tryFrom(is_string($definition['on_scalar']) ? $definition['on_scalar'] : '');
        if (!$hasTraversal || !$action instanceof ScalarAction) {
            throw new InvalidProfileDefinition('on_scalar must be redact or preserve and requires children or each');
        }

        return $action;
    }

    /** @param array<string, mixed> $definition */
    public static function strategy(array $definition): ?string
    {
        if (!array_key_exists('strategy', $definition)) {
            return null;
        }
        if (!is_string($definition['strategy']) || $definition['strategy'] === '') {
            throw new InvalidProfileDefinition('strategy must be a non-empty string');
        }

        return $definition['strategy'];
    }

    /** @param array<string, mixed> $definition */
    public static function embedded(array $definition): ?EmbeddedPayloadDefinition
    {
        if (!array_key_exists('embedded', $definition)) {
            return null;
        }
        if (!is_array($definition['embedded']) || array_keys($definition['embedded']) !== ['codec', 'profile']) {
            throw new InvalidProfileDefinition('embedded must be a map with exactly codec and profile');
        }
        if (!is_string($definition['embedded']['codec']) || $definition['embedded']['codec'] === '' || !is_string($definition['embedded']['profile']) || $definition['embedded']['profile'] === '') {
            throw new InvalidProfileDefinition('embedded codec and profile must be non-empty strings');
        }

        return new EmbeddedPayloadDefinition($definition['embedded']['codec'], $definition['embedded']['profile']);
    }
}
