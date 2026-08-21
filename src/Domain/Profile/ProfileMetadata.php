<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use InvalidArgumentException;

final readonly class ProfileMetadata
{
    public function __construct(
        private ?string $schemaVersion = null,
        private ?string $profileVersion = null,
        private ?string $owner = null,
        private ?string $status = null,
        private ?string $purpose = null,
    ) {
        foreach ([$schemaVersion, $profileVersion, $owner, $purpose] as $value) {
            if ($value !== null && trim($value) === '') {
                throw new InvalidArgumentException('Profile metadata values must not be empty.');
            }
        }

        if ($status !== null && !in_array($status, ['active', 'deprecated'], true)) {
            throw new InvalidArgumentException('Profile metadata status must be active or deprecated.');
        }
    }

    public function schemaVersion(): ?string
    {
        return $this->schemaVersion;
    }

    public function profileVersion(): ?string
    {
        return $this->profileVersion;
    }

    public function owner(): ?string
    {
        return $this->owner;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function purpose(): ?string
    {
        return $this->purpose;
    }
}
