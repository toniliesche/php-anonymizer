<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Payload;

interface DocumentPayloadCodecInterface extends PayloadCodecInterface
{
    /**
     * @return array<mixed, mixed>
     */
    public function decodeDocument(string $payload): array;

    /**
     * @param array<mixed, mixed> $data
     */
    public function encodeDocument(array $data): string;
}
