<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Payload;

use JsonException;
use Override;
use function Safe\json_decode;
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
        $first = ltrim($payload)[0] ?? '';
        if ($first !== '{') {
            throw new JsonException('JSON payload root must be an object.');
        }

        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException('JSON payload root must be an object.');
        }

        /** @var non-empty-array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @return array<mixed, mixed>
     */
    #[Override]
    public function decodeDocument(string $payload): array
    {
        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new JsonException('JSON document root must be an object or array.');
        }

        return $decoded;
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
}
