<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule\Compiler;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;

/**
 * @internal
 */
final class EmbeddedPayloadDefinitionParser
{
    /**
     * @param array<string, mixed> $definition
     */
    public static function parse(array $definition): ?EmbeddedPayloadDefinition
    {
        if (!array_key_exists('embedded', $definition)) {
            return null;
        }
        $embedded = $definition['embedded'];
        if (!is_array($embedded) || array_keys($embedded) !== ['codec', 'profile']) {
            throw new InvalidProfileDefinition('embedded must be a map with exactly codec and profile');
        }
        if (!is_string($embedded['codec']) || $embedded['codec'] === '' || !is_string($embedded['profile']) || $embedded['profile'] === '') {
            throw new InvalidProfileDefinition('embedded codec and profile must be non-empty strings');
        }

        return new EmbeddedPayloadDefinition($embedded['codec'], $embedded['profile']);
    }
}
