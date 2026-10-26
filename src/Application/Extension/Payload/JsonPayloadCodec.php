<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Payload;

use Override;
use Webmozart\Assert\Assert;
use function Safe\json_encode;

// @internal
final class JsonPayloadCodec implements DocumentPayloadCodecInterface
{
    #[Override]
    public function id(): string
    {
        return 'json';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function decode(string $payload): array
    {
        $data = $this->decodeWithShape($payload)->data();
        Assert::isNonEmptyMap($data);

        return $data;
    }

    /**
     * @return array<mixed, mixed>
     */
    #[Override]
    public function decodeDocument(string $payload): array
    {
        return $this->decodeDocumentWithShape($payload)->data();
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<mixed, mixed> $data
     */
    #[Override]
    public function encodeDocument(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * @internal
     */
    public function decodeWithShape(string $payload): JsonPayloadData
    {
        return JsonPayloadData::decodeMap($payload);
    }

    /**
     * @internal
     */
    public function decodeDocumentWithShape(string $payload): JsonPayloadData
    {
        return JsonPayloadData::decodeDocument($payload);
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $invalidatedPaths
     *
     * @internal
     */
    public function encodeWithShape(array $data, JsonPayloadData $decoded, array $invalidatedPaths): string
    {
        return $decoded->encode($data, $invalidatedPaths);
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<string> $invalidatedPaths
     *
     * @internal
     */
    public function encodeDocumentWithShape(array $data, JsonPayloadData $decoded, array $invalidatedPaths): string
    {
        return $decoded->encode($data, $invalidatedPaths);
    }
}
