<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DeferredNdjsonAuditProvider;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAuditProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAuditResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRoot;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * @internal NDJSON operation extracted from the core engine
 */
final readonly class NdjsonAnonymizationService
{
    public function __construct(private ProfileRegistry $profiles, private ArrayTraversal $traversal, private AnonymizationLimits $limits)
    {
    }

    /**
     * @param iterable<mixed> $lines
     */
    public function anonymize(string $profile, iterable $lines, ?AnonymizationContext $context = null): NdjsonAnonymizationResult
    {
        $definition = $this->profiles->get($profile);
        if (!$definition instanceof ProfileDefinition) {
            throw ProfileNotFound::named($profile);
        }
        if ($definition->root() !== ProfileRoot::LIST) {
            throw new InvalidArgumentException(sprintf('Profile "%s" requires root=list for NDJSON.', $profile));
        }

        $counts = [];
        $auditProvider = new DeferredNdjsonAuditProvider();
        $output = $this->stream($profile, $definition, $lines, $context, $counts, $auditProvider);

        return new NdjsonAnonymizationResult($output, $auditProvider);
    }

    /**
     * @param iterable<mixed> $lines
     * @param array<string, array{path: string, action: string, handler: ?string, count: int}> $counts
     *
     * @return iterable<string>
     */
    private function stream(string $profile, ProfileDefinition $definition, iterable $lines, ?AnonymizationContext $context, array &$counts, NdjsonAuditProviderInterface $auditProvider): iterable
    {
        $json = new JsonPayloadCodec();
        foreach ($lines as $ordinal => $line) {
            yield $this->processLine($profile, $ordinal, $line, $context, $counts, $json);
        }
        ksort($counts);
        $entries = [];
        foreach ($counts as $entry) {
            $entries[] = new AuditEntry($profile, $definition->fingerprint(), $entry['path'], $entry['action'], $entry['handler'], $entry['count']);
        }
        $auditProvider->complete(new NdjsonAuditResult($entries));
    }

    /**
     * @param array<string, array{path: string, action: string, handler: ?string, count: int}> $counts
     */
    private function processLine(string $profile, mixed $ordinal, mixed $line, ?AnonymizationContext $context, array &$counts, JsonPayloadCodec $json): string
    {
        $line = $this->line($ordinal, $line);
        $budget = new ExecutionBudget($this->limits);

        $budget->inputBytes($profile, sprintf('record %s', $this->formatOrdinal($ordinal)), mb_strlen($line, '8bit'));

        try {
            $decoded = $json->decodeDocument($line);
        } catch (Throwable $exception) {
            throw PayloadCodecFailure::topLevel($profile, 'json', 'decode', $exception);
        }
        if ((ltrim($line)[0] ?? '') !== '{') {
            throw new InvalidArgumentException(sprintf('NDJSON record %s must be a JSON object.', $this->formatOrdinal($ordinal)));
        }

        Assert::isMap($decoded);
        $map = $decoded;
        $result = $this->traversal->apply($profile, $map, true, $budget, $context);
        foreach ($result->events() as $event) {
            $path = '/*' . $event['path'];
            $key = implode("\0", [$path, $event['action'], $event['handler'] ?? '']);
            $counts[$key] ??= ['path' => $path, 'action' => $event['action'], 'handler' => $event['handler'], 'count' => 0];
            ++$counts[$key]['count'];
        }

        return $json->encodeDocument($result->data());
    }

    private function line(mixed $ordinal, mixed $line): string
    {
        if (!is_string($line)) {
            throw new InvalidArgumentException(sprintf('NDJSON record %s must be a string.', $this->formatOrdinal($ordinal)));
        }
        $line = rtrim($line, "\r\n");
        if ($line === '') {
            throw new InvalidArgumentException(sprintf('NDJSON record %s must not be blank.', $this->formatOrdinal($ordinal)));
        }

        return $line;
    }

    private function formatOrdinal(mixed $ordinal): string
    {
        return is_int($ordinal) || is_string($ordinal) ? (string) $ordinal : get_debug_type($ordinal);
    }
}
