<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Rule\Payload;

final readonly class EmbeddedPayloadDefinition
{
    public function __construct(
        private string $codec,
        private string $profile,
    ) {
    }

    public function codec(): string
    {
        return $this->codec;
    }

    public function profile(): string
    {
        return $this->profile;
    }
}
