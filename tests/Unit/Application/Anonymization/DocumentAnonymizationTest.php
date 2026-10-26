<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use ArrayIterator;
use InvalidArgumentException;
use LogicException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFacade;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerRuntime;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\NdjsonAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\PayloadAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DeferredNdjsonAuditProvider;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAuditResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\RuntimeAssembler;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\CompiledProfile;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\CompiledRuleList;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleSelector;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleTreeTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\SelectedRule;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\SelectedValueDispatcher;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\TraversalExecutionContext;
use PhpAnonymizer\Anonymizer\Application\Exception\ExecutionLimitExceeded;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadData;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\EmbeddedProfileReferenceGraph;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\CoreTransformationFactory;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\DateGeneralizeTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\NullifyTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\NumericBucketTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\PartialMaskTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileGlobalRuleParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRoot;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\EmbeddedPayloadDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\ConditionalDocumentPayloadCodec;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\RecordingPayloadCodec;
use PhpAnonymizer\Anonymizer\Tests\Support\PullCountingIterable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(DocumentAnonymizationResult::class)]
#[CoversClass(DocumentPayloadAnonymizationResult::class)]
#[UsesClass(PayloadAnonymizationResult::class)]
#[CoversClass(NdjsonAnonymizationResult::class)]
#[CoversClass(NdjsonAuditResult::class)]
#[CoversClass(NdjsonAnonymizationService::class)]
#[CoversClass(PayloadAnonymizationService::class)]
#[CoversClass(JsonPayloadCodec::class)]
#[CoversClass(JsonPayloadData::class)]
#[CoversClass(ProfileDefinition::class)]
#[CoversClass(ProfileRoot::class)]
#[UsesClass(AnonymizationContext::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactTraversalPolicy::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(AuditEntry::class)]
#[CoversClass(ArrayTraversal::class)]
#[UsesClass(ExecutionBudget::class)]
#[CoversClass(RuleTreeTraversal::class)]
#[UsesClass(CompiledProfile::class)]
#[UsesClass(CompiledRuleList::class)]
#[UsesClass(RuleSelector::class)]
#[UsesClass(SelectedRule::class)]
#[UsesClass(EmbeddedPayloadTraversal::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(ProfileReferenceValidator::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(CoreTransformationFactory::class)]
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
#[UsesClass(ExecutionLimitExceeded::class)]
#[UsesClass(PayloadCodecFailure::class)]
#[UsesClass(ProfileNotFound::class)]
#[UsesClass(RuntimeAssembler::class)]
#[UsesClass(SelectedValueDispatcher::class)]
#[UsesClass(TraversalExecutionContext::class)]
#[UsesClass(AnonymizerRuntime::class)]
#[UsesClass(AnonymizerFacade::class)]
#[CoversClass(DeferredNdjsonAuditProvider::class)]
#[UsesClass(DateGeneralizeTransformation::class)]
#[UsesClass(NullifyTransformation::class)]
#[UsesClass(NumericBucketTransformation::class)]
#[UsesClass(PartialMaskTransformation::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(ProfileGlobalRuleParser::class)]
#[UsesClass(EmbeddedPayloadDefinitionParser::class)]
#[UsesClass(EmbeddedPayloadDefinition::class)]
#[UsesClass(RuleTraversalCombinationValidator::class)]
final class DocumentAnonymizationTest extends TestCase
{
    public function testDeferredAuditProviderRequiresCompletionBeforeAccess(): void
    {
        $provider = new DeferredNdjsonAuditProvider();
        $this->expectException(LogicException::class);
        $provider->audit();
    }

    public function testDeferredAuditProviderReturnsCompletedAudit(): void
    {
        $provider = new DeferredNdjsonAuditProvider();
        $audit = new NdjsonAuditResult([]);
        $provider->complete($audit);

        self::assertSame($audit, $provider->audit());
    }

    public function testDocumentListRootIsExplicitAndReported(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $result = $anonymizer->anonymizeDocumentWithReport('records', 'json', '[{"email":"one"},{"email":"two"}]');

        self::assertSame('[{"email":"[REDACTED]"},{"email":"[REDACTED]"}]', $result->payload());
        self::assertSame(['/0/email', '/1/email'], $result->anonymizationResult()->anonymizedPaths());
    }

    public function testNdjsonResultCanUseAProviderFreeAuditFactory(): void
    {
        $result = NdjsonAnonymizationResult::fromLines([], static fn (): NdjsonAuditResult => new NdjsonAuditResult([]));

        self::assertSame([], $result->audit()->entries());
    }

    public function testNdjsonResultRejectsAnInvalidAuditFactoryResult(): void
    {
        $this->expectException(LogicException::class);

        NdjsonAnonymizationResult::fromLines([], static fn (): mixed => null)->audit();
    }

    public function testMapDocumentRetainsLegacyShape(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        self::assertSame('{"email":"[REDACTED]"}', $anonymizer->anonymizeDocument('customer', 'json', '{"email":"secret"}'));
        self::assertSame(ProfileRoot::MAP, $profile->root());
    }

    public function testJsonOperationsPreserveEmptyObjectAndArrayShapes(): void
    {
        $mapProfile = ProfileDefinition::fromArray('preserve-map', ['mode' => 'preserve', 'rules' => [['field' => 'keep']]]);
        $listProfile = ProfileDefinition::fromArray('preserve-list', ['mode' => 'preserve', 'root' => 'list', 'rules' => [['field' => 'keep']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$mapProfile, $listProfile]));

        self::assertSame('{"nested":{},"array":[]}', $anonymizer->anonymizePayload('preserve-map', 'json', '{"nested":{},"array":[]}'));
        self::assertSame('{"nested":{},"array":[]}', $anonymizer->anonymizeDocument('preserve-map', 'json', '{"nested":{},"array":[]}'));
        self::assertSame('[{},[],{"nested":{}}]', $anonymizer->anonymizeDocument('preserve-list', 'json', '[{},[],{"nested":{}}]'));
        self::assertSame('{}', $anonymizer->anonymizeDocument('preserve-map', 'json', '{}'));
        self::assertSame(['{}', '{"nested":[]}'], iterator_to_array(
            $anonymizer->anonymizeNdjson('preserve-list', ['{}', '{"nested":[]}'])->lines(),
            false,
        ));
    }

    public function testEmbeddedJsonPreservesEmptyObjectShape(): void
    {
        $metadata = ProfileDefinition::fromArray('metadata', ['mode' => 'preserve', 'rules' => [['field' => 'keep']]]);
        $outer = ProfileDefinition::fromArray('outer', [
            'mode' => 'preserve',
            'rules' => [['field' => 'metadata', 'embedded' => ['codec' => 'json', 'profile' => 'metadata']]],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$outer, $metadata]));

        self::assertSame('{"metadata":"{\"empty\":{},\"list\":[]}"}', $anonymizer->anonymizePayload('outer', 'json', '{"metadata":"{\"empty\":{},\"list\":[]}"}'));
    }

    public function testNdjsonIsLazyAndAggregatesWildcardAudit(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
        $stream = $anonymizer->anonymizeNdjson('records', ['{"email":"one"}', '{"email":"two"}']);

        $this->expectException(LogicException::class);
        $stream->audit();
    }

    public function testNdjsonDoesNotPullSourceBeforeLinesAreRequested(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);

        $lines = new PullCountingIterable(['{"email":"one"}']);
        $stream = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymizeNdjson('records', $lines);

        self::assertSame(0, $lines->pulls());
        iterator_to_array($stream->lines(), false);
        self::assertSame(1, $lines->pulls());
    }

    public function testNdjsonOutputAndAuditAfterExhaustion(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
        $stream = $anonymizer->anonymizeNdjson('records', ["{\"email\":\"one\"}\n", '{"email":"two"}']);

        self::assertSame(['{"email":"[REDACTED]"}', '{"email":"[REDACTED]"}'], iterator_to_array($stream->lines(), false));
        $audit = $stream->audit();

        self::assertSame('/*/email', $audit->entries()[0]->path());
        self::assertSame(2, $audit->entries()[0]->count());
    }

    public function testNdjsonAcceptsWhitespaceAndSortsAggregatedAuditEntries(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email'], ['field' => 'name']],
            'root' => 'list',
        ]);
        $stream = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymizeNdjson(
            'records',
            ["  {\"name\":\"one\",\"email\":\"one@example.test\"}\r\n"],
        );

        self::assertSame(['{"name":"[REDACTED]","email":"[REDACTED]"}'], iterator_to_array($stream->lines(), false));
        self::assertSame(['/email', '/name'], array_map(static fn (AuditEntry $entry): string => mb_substr($entry->path(), 2, null, 'UTF-8'), $stream->audit()->entries()));
    }

    public function testNdjsonRejectsNonObjectRecordsWhenPulled(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $stream = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymizeNdjson('records', ['[]']);

        $this->expectException(InvalidArgumentException::class);
        iterator_to_array($stream->lines(), false);
    }

    public function testNdjsonRejectsEveryNonObjectJsonValue(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact', 'rules' => [['field' => 'email']], 'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        foreach (['[1]'] as $line) {
            try {
                iterator_to_array($anonymizer->anonymizeNdjson('records', [$line])->lines(), false);
                self::fail('Expected non-object record to be rejected.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('must be a JSON object', $exception->getMessage());
            }
        }
        foreach (['null', 'true', '"text"'] as $line) {
            try {
                iterator_to_array($anonymizer->anonymizeNdjson('records', [$line])->lines(), false);
                self::fail('Expected scalar JSON payload to fail decoding.');
            } catch (PayloadCodecFailure $exception) {
                self::assertStringContainsString('failed to decode', $exception->getMessage());
            }
        }
    }

    public function testNdjsonAggregatesDistinctActionsAndMissingEventsInStableOrder(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email'], ['field' => 'name']],
            'root' => 'list',
        ]);
        $stream = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeNdjson('records', ['{"name":"Ada"}', '{"email":"secret"}']);

        self::assertSame([
            '{"name":"[REDACTED]"}',
            '{"email":"[REDACTED]"}',
        ], iterator_to_array($stream->lines(), false));
        $entries = $stream->audit()->entries();
        self::assertCount(4, $entries);
        self::assertSame([
            ['/*/email', 'missing'], ['/*/email', 'replace'],
            ['/*/name', 'missing'], ['/*/name', 'replace'],
        ], array_map(static fn (AuditEntry $entry): array => [$entry->path(), $entry->action()], $entries));
        self::assertSame([1, 1, 1, 1], array_map(static fn (AuditEntry $entry): int => $entry->count(), $entries));
    }

    public function testNdjsonRejectsBlankAndNonStringRecordsWhenPulled(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            iterator_to_array($anonymizer->anonymizeNdjson('records', ["\n"])->lines(), false);
            self::fail('Expected blank NDJSON record to be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('must not be blank', $exception->getMessage());
        }

        try {
            iterator_to_array($anonymizer->anonymizeNdjson('records', [42])->lines(), false);
            self::fail('Expected non-string NDJSON record to be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('must be a string', $exception->getMessage());
        }
    }

    public function testNdjsonFormatsStringOrdinalsInValidationErrors(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'root' => 'list',
            'rules' => [['field' => 'email']],
        ]);
        $lines = new ArrayIterator(['record-key' => 42]);
        $stream = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeNdjson('records', $lines);

        try {
            iterator_to_array($stream->lines(), false);
            self::fail('Expected a non-string NDJSON record to be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('record record-key must be a string', $exception->getMessage());
        }
    }

    public function testNdjsonRequiresListProfile(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $this->expectException(InvalidArgumentException::class);
        $anonymizer->anonymizeNdjson('customer', []);
    }

    public function testContextualDocumentMethodAcceptsContext(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        self::assertSame('[{"email":"[REDACTED]"}]', $anonymizer->anonymizeDocument('records', 'json', '[{"email":"secret"}]', new AnonymizationContext(['tenant' => 'acme'])));
    }

    public function testContextualNdjsonMethodProcessesRecords(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $stream = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymizeNdjson('records', ['{"email":"secret"}'], new AnonymizationContext(['tenant' => 'acme']));

        self::assertSame(['{"email":"[REDACTED]"}'], iterator_to_array($stream->lines(), false));
    }

    public function testDocumentRejectsRootShapesAndListItems(): void
    {
        $mapProfile = ProfileDefinition::fromArray('map', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $listProfile = ProfileDefinition::fromArray('list', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$mapProfile, $listProfile]));

        try {
            $anonymizer->anonymizeDocument('map', 'json', '  []');
            self::fail('A map profile must reject a JSON list document.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Profile "map" root does not match the document root.', $exception->getMessage());
        }

        $shapeCodec = new ConditionalDocumentPayloadCodec();
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$mapProfile, $listProfile]), codecs: [$shapeCodec]);

        try {
            $anonymizer->anonymizeDocument('map', 'shape', 'list');
            self::fail('A map profile must reject a list document.');
        } catch (InvalidArgumentException) {
        }

        try {
            $anonymizer->anonymizeDocument('list', 'shape', 'map');
            self::fail('A list profile must reject a map document.');
        } catch (InvalidArgumentException) {
        }
        $this->expectException(InvalidArgumentException::class);
        $anonymizer->anonymizeDocument('list', 'json', '["scalar"]');
    }

    public function testDocumentInputLimitCountsBytesAndChecksWhitespaceBeforeJsonShape(): void
    {
        $profile = ProfileDefinition::fromArray('unicode-document', ['mode' => 'redact', 'rules' => [['field' => 'payload']]]);
        foreach ([['abc', 3, true], ['abcd', 3, false], ['€', 3, true], ['€a', 3, false]] as [$payload, $limit, $withinLimit]) {
            $codec = new RecordingPayloadCodec('recording', ['payload' => 'secret']);
            $anonymizer = new ArrayAnonymizer(
                new InMemoryProfileProvider([$profile]),
                codecs: [$codec],
                limits: new AnonymizationLimits(maxInputBytes: $limit),
            );

            if ($withinLimit) {
                $anonymizer->anonymizeDocument('unicode-document', 'recording', $payload);
                self::assertSame(1, $codec->documentDecodeCalls);

                continue;
            }

            try {
                $anonymizer->anonymizeDocument('unicode-document', 'recording', $payload);
                self::fail('Documents larger than the byte limit should be rejected.');
            } catch (ExecutionLimitExceeded) {
                self::assertSame(0, $codec->documentDecodeCalls);
            }
        }

        $list = ProfileDefinition::fromArray('list-root', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$list]))->anonymizeDocument('list-root', 'json', "\n  {\"email\":\"secret\"}");
            self::fail('A whitespace-prefixed JSON object must not pass a list-root profile.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Profile "list-root" root does not match the document root.', $exception->getMessage());
        }
    }

    public function testDocumentResultExposesAllReportCollections(): void
    {
        $result = new DocumentAnonymizationResult(['email' => 'x'], ['/email'], ['/name'], [['path' => '/email', 'action' => 'transform', 'handler' => 'mask']]);

        self::assertSame(['email' => 'x'], $result->data());
        self::assertSame(['/email'], $result->anonymizedPaths());
        self::assertSame(['/name'], $result->missingPaths());
        self::assertSame([['path' => '/email', 'action' => 'transform', 'handler' => 'mask']], $result->events());
    }

    public function testDocumentCodecFailuresAreSafe(): void
    {
        $profile = ProfileDefinition::fromArray('map', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new InMemoryProfileProvider([$profile]);
        $anonymizer = new ArrayAnonymizer($provider);
        $this->expectException(InvalidArgumentException::class);
        $anonymizer->anonymizeDocument('map', 'missing', '{}');
    }

    public function testDocumentCoversMissingProfileAndEncodingFailure(): void
    {
        $profile = ProfileDefinition::fromArray('map', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new InMemoryProfileProvider([$profile]);
        $anonymizer = new ArrayAnonymizer($provider);

        try {
            $anonymizer->anonymizeDocument('missing', 'json', '{}');
            self::fail('Unknown profile must fail.');
        } catch (RuntimeException) {
        }
        $failingCodec = new ConditionalDocumentPayloadCodec('failing', true);
        $this->expectException(PayloadCodecFailure::class);
        new ArrayAnonymizer($provider, codecs: [$failingCodec])->anonymizeDocument('map', 'failing', '{}');
    }

    public function testDocumentRejectsMalformedAndOversizedInput(): void
    {
        $profile = ProfileDefinition::fromArray('map', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new InMemoryProfileProvider([$profile]);
        $anonymizer = new ArrayAnonymizer($provider);

        try {
            $anonymizer->anonymizeDocument('map', 'json', '{');
            self::fail('Malformed JSON must fail.');
        } catch (RuntimeException) {
        }
        $limited = new ArrayAnonymizer($provider, limits: new AnonymizationLimits(maxInputBytes: 1));
        $this->expectException(ExecutionLimitExceeded::class);
        $limited->anonymizeDocument('map', 'json', '{}');
    }

    public function testNdjsonRejectsBlankAndMalformedRecords(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            iterator_to_array($anonymizer->anonymizeNdjson('records', [])->lines(), false);
        } catch (Throwable) {
            self::fail('An empty stream is valid.');
        }
        foreach ([[''], ['{']] as $lines) {
            $this->expectException(Throwable::class);
            iterator_to_array($anonymizer->anonymizeNdjson('records', $lines)->lines(), false);
        }
    }

    public function testNdjsonRejectsMalformedJsonRecord(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $this->expectException(PayloadCodecFailure::class);
        iterator_to_array($anonymizer->anonymizeNdjson('records', ['{'])->lines(), false);
    }

    public function testNdjsonCoversNonStringAndMissingProfile(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array($anonymizer->anonymizeNdjson('records', [42])->lines(), false);
    }

    public function testJsonDocumentRejectsScalarRootAndUnknownNdjsonProfile(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            $anonymizer->anonymizeDocument('records', 'json', 'null');
            self::fail('Scalar JSON documents must fail.');
        } catch (PayloadCodecFailure) {
        }
        $this->expectException(RuntimeException::class);
        $anonymizer->anonymizeNdjson('missing', []);
    }
}
