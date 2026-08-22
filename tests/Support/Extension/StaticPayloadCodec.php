<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use Override;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use RuntimeException;

final readonly class StaticPayloadCodec implements PayloadCodecInterface
{
    /**
     * @param null|array<mixed, mixed> $decoded
     */
    public function __construct(private string $codecId, private ?array $decoded = null, private string $decodeKey = 'payload', private ?string $encodingFailure = null)
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
        return $this->decoded ?? [$this->decodeKey => $payload];
    }

    #[Override]
    public function encode(array $data): string
    {
        if ($this->encodingFailure !== null) {
            throw new RuntimeException($this->encodingFailure);
        }
        $value = $data[$this->decodeKey] ?? '';

        return is_string($value) ? $value : '';
    }
}
