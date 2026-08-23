<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;

final readonly class AnonymizationContext
{
    /** @var array<string, string> */
    private array $attributes;

    /**
     * @param array<mixed, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $normalized = [];
        foreach ($attributes as $name => $value) {
            if (!is_string($name) || $name === '' || !is_string($value)) {
                throw new InvalidArgumentException('Anonymization context attributes must be a non-empty string-to-string map.');
            }
            $normalized[$name] = $value;
        }
        ksort($normalized, SORT_STRING);
        $this->attributes = $normalized;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
