<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use Override;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\DocumentPayloadCodecInterface;
use RuntimeException;

final readonly class StaticDocumentPayloadCodec implements DocumentPayloadCodecInterface
{
    /**
     * @param array<mixed, mixed> $decodedDocument
     */
    public function __construct(private string $codecId, private array $decodedDocument = [], private bool $failDocumentEncoding = false)
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
        return ['payload' => $payload];
    }

    #[Override]
    public function encode(array $data): string
    {
        return '';
    }

    #[Override]
    public function decodeDocument(string $payload): array
    {
        return $this->decodedDocument;
    }

    #[Override]
    public function encodeDocument(array $data): string
    {
        if ($this->failDocumentEncoding) {
            throw new RuntimeException('encode failed');
        }

        return '{}';
    }
}
