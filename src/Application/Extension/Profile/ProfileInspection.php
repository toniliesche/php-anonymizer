<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile;

use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadata;

final readonly class ProfileInspection
{
    /**
     * @param list<string> $fragmentNames
     */
    public function __construct(
        private string $name,
        private ?ProfileMetadata $metadata,
        private string $fingerprint,
        private array $fragmentNames,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function metadata(): ?ProfileMetadata
    {
        return $this->metadata;
    }

    public function fingerprint(): string
    {
        return $this->fingerprint;
    }

    /**
     * @return list<string>
     */
    public function fragmentNames(): array
    {
        return $this->fragmentNames;
    }
}
