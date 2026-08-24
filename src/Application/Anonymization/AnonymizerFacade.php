<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use LogicException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResultInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Exception\ObjectAnonymizationUnavailable;
use PhpAnonymizer\Anonymizer\Application\Extension\ObjectAnonymizerInterface;

/**
 * @internal construct through AnonymizerBuilder
 */
final readonly class AnonymizerFacade implements AnonymizerInterface
{
    private ArrayAnonymizer $arrayAnonymizer;

    public function __construct(AnonymizerRuntime $runtime, private ?ObjectAnonymizerInterface $objectAnonymizer = null)
    {
        $this->arrayAnonymizer = new ArrayAnonymizer($runtime);
    }

    #[Override]
    public function anonymize(string $profile, array|object $data, ?AnonymizationContext $context = null): array|object
    {
        if (is_array($data)) {
            return $this->arrayAnonymizer->anonymize($profile, $data, $context);
        }

        return $this->anonymizeObject(new ObjectAnonymizationRequest($profile, $data, $context));
    }

    #[Override]
    public function anonymizeWithReport(string $profile, array|object $data, ?AnonymizationContext $context = null): AnonymizationResultInterface
    {
        if (is_array($data)) {
            return $this->arrayAnonymizer->anonymizeWithReport($profile, $data, $context);
        }

        return $this->anonymizeObjectWithReport(new ObjectAnonymizationRequest($profile, $data, $context));
    }

    #[Override]
    public function anonymizePayload(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): string
    {
        return $this->arrayAnonymizer->anonymizePayload($profile, $codec, $payload, $context);
    }

    #[Override]
    public function anonymizePayloadWithReport(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): PayloadAnonymizationResult
    {
        return $this->arrayAnonymizer->anonymizePayloadWithReport($profile, $codec, $payload, $context);
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    #[Override]
    public function anonymizeRecords(string $profile, iterable $records, ?AnonymizationContext $context = null): iterable
    {
        return $this->arrayAnonymizer->anonymizeRecords($profile, $records, $context);
    }

    #[Override]
    public function anonymizeWithAudit(string $profile, array $data, ?AnonymizationContext $context = null): AuditAnonymizationResult
    {
        return $this->arrayAnonymizer->anonymizeWithAudit($profile, $data, $context);
    }

    #[Override]
    public function anonymizeDocument(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): string
    {
        return $this->arrayAnonymizer->anonymizeDocument($profile, $codec, $payload, $context);
    }

    #[Override]
    public function anonymizeDocumentWithReport(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): DocumentPayloadAnonymizationResult
    {
        return $this->arrayAnonymizer->anonymizeDocumentWithReport($profile, $codec, $payload, $context);
    }

    /**
     * @param iterable<mixed> $lines
     */
    #[Override]
    public function anonymizeNdjson(string $profile, iterable $lines, ?AnonymizationContext $context = null): NdjsonAnonymizationResult
    {
        return $this->arrayAnonymizer->anonymizeNdjson($profile, $lines, $context);
    }

    #[Override]
    public function anonymizeObject(ObjectAnonymizationRequest $request): object
    {
        $data = $this->anonymizeObjectWithReport($request)->data();
        // @codeCoverageIgnoreStart anonymizeObjectWithReport validates this invariant before returning.
        if (!is_object($data)) {
            // @codeCoverageIgnoreStart Defensive return-contract violation.
            throw new LogicException('Object anonymizer returned a non-object result.'); // @codeCoverageIgnore
            // @codeCoverageIgnoreEnd
        }
        // @codeCoverageIgnoreEnd

        return $data;
    }

    #[Override]
    public function anonymizeObjectWithReport(ObjectAnonymizationRequest $request): AnonymizationResultInterface
    {
        if (!$this->objectAnonymizer instanceof ObjectAnonymizerInterface) {
            throw ObjectAnonymizationUnavailable::forObject($request->object());
        }

        $result = $this->objectAnonymizer->anonymize($request, $this);
        $data = $result->data();
        if (!is_object($data) || $data::class !== $request->object()::class) {
            throw new LogicException(sprintf('Object anonymizer must return an object of type %s.', $request->object()::class));
        }

        return $result;
    }
}
