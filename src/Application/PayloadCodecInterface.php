<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

interface PayloadCodecInterface
{
    public function id(): string;

    /** @return array<mixed, mixed> */
    public function decode(string $payload): array;

    /** @param array<string, mixed> $data */
    public function encode(array $data): string;
}
