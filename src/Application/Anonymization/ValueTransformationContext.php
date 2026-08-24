<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;

final readonly class ValueTransformationContext
{
    public function __construct(private string $profile, private JsonPointer $path, private ?string $keyId, private AnonymizationContext $anonymizationContext)
    {
    }

    public function profile(): string
    {
        return $this->profile;
    }

    public function path(): JsonPointer
    {
        return $this->path;
    }

    public function pathString(): string
    {
        return (string) $this->path;
    }

    public function keyId(): ?string
    {
        return $this->keyId;
    }

    /**
     * @return array<string,string>
     */
    public function attributes(): array
    {
        return $this->anonymizationContext->attributes();
    }
}
