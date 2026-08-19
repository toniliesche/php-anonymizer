<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

final readonly class AuditEntry
{
    public function __construct(
        private string $profile,
        private string $fingerprint,
        private string $path,
        private string $action,
        private ?string $handler,
        private int $count,
    ) {}

    public function profile(): string
    {
        return $this->profile;
    }

    public function fingerprint(): string
    {
        return $this->fingerprint;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function handler(): ?string
    {
        return $this->handler;
    }

    public function count(): int
    {
        return $this->count;
    }
}
