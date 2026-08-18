<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Payload;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use Throwable;

final readonly class EmbeddedPayloadTraversal
{
    public function __construct(
        private PayloadCodecRegistry $codecs,
        private ArrayTraversal $nested,
    ) {}

    /**
     */
    public function apply(string $profile, mixed &$value, string $path, string $codecId, string $nestedProfile, AnonymizationReport $report): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        $codec = $this->codecs->get($codecId);
        if (!$codec instanceof PayloadCodecInterface) {
            throw new PayloadCodecFailure(sprintf('Payload codec "%s" is not registered.', $codecId));
        }
        try {
            $decoded = $codec->decode($value);
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::embedded($profile, $codecId, 'decode', $path, $exception);
        }
        /** @var array<string, mixed> $decoded */
        $result = $this->nested->apply($nestedProfile, $decoded);
        try {
            $value = $codec->encode($result->data());
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::embedded($profile, $codecId, 'encode', $path, $exception);
        }
        $base = JsonPointer::fromString($path);
        $report->merge($result, $base);
    }
}
