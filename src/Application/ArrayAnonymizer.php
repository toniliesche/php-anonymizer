<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;

final readonly class ArrayAnonymizer implements AnonymizerInterface
{
    public function __construct(private ProfileProviderInterface $profiles) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[\Override]
    public function anonymize(string $profile, array $data): array
    {
        $definition = $this->profiles->get($profile);
        if (!$definition instanceof \PhpAnonymizer\Anonymizer\Domain\ProfileDefinition) {
            throw ProfileNotFound::named($profile);
        }

        $result = $data;
        foreach ($definition->rules() as $rule) {
            $field = $rule->field();
            if (!array_key_exists($field, $result)) {
                continue;
            }

            if (!is_string($result[$field])) {
                throw UnsupportedValue::at($profile, $field, $result[$field]);
            }

            $result[$field] = '[REDACTED]';
        }

        return $result;
    }
}
