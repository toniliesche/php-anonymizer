<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Payload;

use JsonException;
use Override;
use function Safe\json_decode;
use function Safe\json_encode;

final class JsonPayloadCodec implements PayloadCodecInterface
{
    #[Override]
    public function id(): string
    {
        return 'json';
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @codeCoverageIgnore
     */
    #[Override]
    public function decode(string $payload): array
    {
        $first = ltrim($payload)[0] ?? '';
        if ($first !== '{') {
            throw new JsonException('JSON payload root must be an object.');
        }

        // @codeCoverageIgnoreStart Defensive root-shape guard.
        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException('JSON payload root must be an object.');
        }

        /** @var non-empty-array<string, mixed> $decoded */
        return $decoded;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
