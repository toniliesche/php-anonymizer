<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use Webmozart\Assert\Assert;

/**
 * @internal metadata schema parser
 */
final class ProfileMetadataParser
{
    /**
     * @param array<string, mixed> $definition
     */
    public function parse(string $name, array $definition): ?ProfileMetadata
    {
        if (!array_key_exists('metadata', $definition)) {
            return null;
        }
        $value = $definition['metadata'];
        if (!is_array($value) || $value === [] || array_is_list($value)) {
            throw InvalidProfileDefinition::forProfile($name, 'metadata must be a non-empty map when provided');
        }

        Assert::isNonEmptyMap($value);
        if (array_diff(array_keys($value), ['schema_version', 'profile_version', 'owner', 'status', 'purpose']) !== []) {
            throw InvalidProfileDefinition::forProfile($name, 'metadata contains an unknown key');
        }
        foreach ($value as $key => $item) {
            if (!is_string($item) || trim($item) === '') {
                throw InvalidProfileDefinition::forProfile($name, sprintf('metadata.%s must be a non-empty string', $key));
            }
        }

        try {
            return new ProfileMetadata($this->value($value, 'schema_version'), $this->value($value, 'profile_version'), $this->value($value, 'owner'), $this->value($value, 'status'), $this->value($value, 'purpose'));
        } catch (InvalidArgumentException $exception) {
            throw InvalidProfileDefinition::at($name, $exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function value(array $metadata, string $key): ?string
    {
        if (!array_key_exists($key, $metadata)) {
            return null;
        }
        $value = $metadata[$key];
        Assert::string($value);

        return $value;
    }
}
