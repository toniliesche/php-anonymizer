<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Payload;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use Throwable;
use Webmozart\Assert\Assert;

// @internal
final readonly class EmbeddedPayloadTraversal
{
    public function __construct(
        private PayloadCodecRegistry $codecs,
        private ArrayTraversal $nested,
    ) {
    }

    public function apply(string $profile, mixed &$value, string $path, string $codecId, string $nestedProfile, AnonymizationReport $report, ?ExecutionBudget $budget = null, ?AnonymizationContext $context = null): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }
        $codec = $this->codecs->get($codecId);
        if (!$codec instanceof PayloadCodecInterface) {
            throw new PayloadCodecFailure(sprintf('Payload codec "%s" is not registered.', $codecId));
        }
        $pointer = JsonPointer::fromString($path);
        $report->event($pointer, 'embedded', $codecId);
        $budget ??= new ExecutionBudget(new AnonymizationLimits());
        $budget->enterEmbedded($profile, $pointer);
        $budget->inputBytes($profile, $path, mb_strlen($value, 'UTF-8'));

        try {
            $decoded = $codec->decode($value);
        } catch (Throwable $exception) {
            $budget->leaveEmbedded();

            throw PayloadCodecFailure::embedded($profile, $codecId, 'decode', $path, $exception);
        }

        Assert::isMap($decoded);

        try {
            $result = $this->nested->apply($nestedProfile, $decoded, $report->isCollecting(), $budget, $context);
        } finally {
            $budget->leaveEmbedded();
        }

        try {
            $value = $codec->encode($result->data());
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::embedded($profile, $codecId, 'encode', $path, $exception);
        }
        $report->merge($result, $pointer);
    }
}
