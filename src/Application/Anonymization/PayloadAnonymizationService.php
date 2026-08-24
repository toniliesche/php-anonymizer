<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Exception\ExecutionLimitExceeded;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\DocumentPayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRoot;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * @internal payload and document operations extracted from the core engine
 */
final readonly class PayloadAnonymizationService
{
    public function __construct(private PayloadCodecRegistry $codecs, private ProfileRegistry $profiles, private ArrayTraversal $traversal, private AnonymizationLimits $limits)
    {
    }

    public function anonymizePayload(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): string
    {
        return $this->anonymizePayloadWithReport($profile, $codec, $payload, $context)->payload();
    }

    public function anonymizePayloadWithReport(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): PayloadAnonymizationResult
    {
        $payloadCodec = $this->codec($codec);

        try {
            new ExecutionBudget($this->limits)->inputBytes($profile, '', mb_strlen($payload, 'UTF-8'));
            $data = $payloadCodec->decode($payload);
        } catch (ExecutionLimitExceeded $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'decode', $exception);
        }
        if (!$this->isMap($data)) {
            throw PayloadCodecFailure::expectedMap($profile, $codec);
        }

        Assert::isMap($data);
        $result = $this->traversal->apply($profile, $data, true, context: $context);

        try {
            $encoded = $payloadCodec->encode($result->data());
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'encode', $exception);
        }

        return new PayloadAnonymizationResult($encoded, $result);
    }

    public function anonymizeDocument(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): string
    {
        return $this->anonymizeDocumentWithReport($profile, $codec, $payload, $context)->payload();
    }

    public function anonymizeDocumentWithReport(string $profile, string $codec, string $payload, ?AnonymizationContext $context = null): DocumentPayloadAnonymizationResult
    {
        $payloadCodec = $this->codec($codec);
        if (!$payloadCodec instanceof DocumentPayloadCodecInterface) {
            throw new InvalidArgumentException(sprintf('Payload codec "%s" does not support document roots.', $codec));
        }

        try {
            new ExecutionBudget($this->limits)->inputBytes($profile, '', mb_strlen($payload, 'UTF-8'));
            $data = $payloadCodec->decodeDocument($payload);
        } catch (ExecutionLimitExceeded $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'decode', $exception);
        }

        $definition = $this->profiles->get($profile);
        if ($definition instanceof ProfileDefinition && $codec === 'json') {
            $first = ltrim($payload)[0] ?? '';
            if (($first === '[' && $definition->root() === ProfileRoot::MAP) || ($first === '{' && $definition->root() === ProfileRoot::LIST)) {
                throw new InvalidArgumentException(sprintf('Profile "%s" root does not match the document root.', $profile));
            }
        }
        $result = $this->traversal->applyDocument($profile, $data, $context);

        try {
            $encoded = $payloadCodec->encodeDocument($result->data());
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, $codec, 'encode', $exception);
        }

        return new DocumentPayloadAnonymizationResult($encoded, $result);
    }

    private function codec(string $codec): PayloadCodecInterface
    {
        $payloadCodec = $this->codecs->get($codec);
        if (!$payloadCodec instanceof PayloadCodecInterface) {
            throw new InvalidArgumentException(sprintf('Payload codec "%s" is not registered.', $codec));
        }

        return $payloadCodec;
    }

    private function isMap(mixed $value): bool
    {
        return is_array($value) && (!array_is_list($value) || $value === []);
    }
}
