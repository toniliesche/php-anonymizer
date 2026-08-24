<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule;

final readonly class TransformationDefinition
{
    /**
     * @param array<string,mixed> $options
     */
    public function __construct(private string $type, private array $options, private ?string $keyId = null)
    {
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array<string,mixed>
     */
    public function options(): array
    {
        return $this->options;
    }

    public function keyId(): ?string
    {
        return $this->keyId;
    }
}
