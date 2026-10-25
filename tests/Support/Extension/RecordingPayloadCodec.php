<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use Override;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\DocumentPayloadCodecInterface;

final class RecordingPayloadCodec implements DocumentPayloadCodecInterface
{
    public int $decodeCalls = 0;

    public int $documentDecodeCalls = 0;

    /**
     * @param array<mixed, mixed> $decoded
     */
    public function __construct(private readonly string $codecId, private readonly array $decoded)
    {
    }

    #[Override]
    public function id(): string
    {
        return $this->codecId;
    }

    #[Override]
    public function decode(string $payload): array
    {
        ++$this->decodeCalls;

        return $this->decoded;
    }

    #[Override]
    public function encode(array $data): string
    {
        return '';
    }

    #[Override]
    public function decodeDocument(string $payload): array
    {
        ++$this->documentDecodeCalls;

        return $this->decoded;
    }

    #[Override]
    public function encodeDocument(array $data): string
    {
        return '';
    }
}
