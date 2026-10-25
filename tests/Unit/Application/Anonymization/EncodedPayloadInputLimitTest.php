<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use LogicException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
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
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
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
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\EmbeddedPayloadDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\RecordingPayloadCodec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(PayloadAnonymizationService::class)]
#[CoversClass(NdjsonAnonymizationService::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactTraversalPolicy::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(PayloadAnonymizationResult::class)]
#[UsesClass(AuditEntry::class)]
#[UsesClass(DeferredNdjsonAuditProvider::class)]
#[UsesClass(NdjsonAnonymizationResult::class)]
#[UsesClass(NdjsonAuditResult::class)]
#[UsesClass(RuntimeAssembler::class)]
#[UsesClass(ExecutionLimitExceeded::class)]
#[UsesClass(ArrayTraversal::class)]
#[UsesClass(ExecutionBudget::class)]
#[UsesClass(RuleTreeTraversal::class)]
#[UsesClass(CompiledProfile::class)]
#[UsesClass(CompiledRuleList::class)]
#[UsesClass(RuleSelector::class)]
#[UsesClass(SelectedRule::class)]
#[UsesClass(SelectedValueDispatcher::class)]
#[UsesClass(TraversalExecutionContext::class)]
#[UsesClass(EmbeddedPayloadTraversal::class)]
#[UsesClass(JsonPayloadCodec::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(ProfileReferenceValidator::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(CoreTransformationFactory::class)]
#[UsesClass(DateGeneralizeTransformation::class)]
#[UsesClass(NullifyTransformation::class)]
#[UsesClass(NumericBucketTransformation::class)]
#[UsesClass(PartialMaskTransformation::class)]
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileGlobalRuleParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(EmbeddedPayloadDefinitionParser::class)]
#[UsesClass(EmbeddedPayloadDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(RuleTraversalCombinationValidator::class)]
#[UsesClass(InMemoryProfileProvider::class)]
final class EncodedPayloadInputLimitTest extends TestCase
{
    public function testPayloadInputLimitUsesBytesAndRejectsBeforeDecoding(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        foreach ([['abc', 3, true], ['abcd', 3, false], ['€', 3, true], ['€a', 3, false]] as [$payload, $limit, $withinLimit]) {
            $codec = new RecordingPayloadCodec('recording', ['email' => 'secret']);
            $anonymizer = new ArrayAnonymizer(
                new InMemoryProfileProvider([$profile]),
                codecs: [$codec],
                limits: new AnonymizationLimits(maxInputBytes: $limit),
            );

            if ($withinLimit) {
                $anonymizer->anonymizePayload('customer', 'recording', $payload);
                self::assertSame(1, $codec->decodeCalls);

                continue;
            }

            try {
                $anonymizer->anonymizePayload('customer', 'recording', $payload);
                self::fail('Payloads larger than the byte limit should be rejected.');
            } catch (ExecutionLimitExceeded) {
                self::assertSame(0, $codec->decodeCalls);
            }
        }
    }

    public function testEmbeddedPayloadInputLimitUsesBytesAndRejectsBeforeDecoding(): void
    {
        $nested = ProfileDefinition::fromArray('nested', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $outer = ProfileDefinition::fromArray('outer', [
            'mode' => 'redact',
            'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'recording', 'profile' => 'nested']]],
        ]);

        foreach ([['abc', 3, true], ['abcd', 3, false], ['€', 3, true], ['€a', 3, false]] as [$payload, $limit, $withinLimit]) {
            $codec = new RecordingPayloadCodec('recording', ['email' => 'secret']);
            $anonymizer = new ArrayAnonymizer(
                new InMemoryProfileProvider([$outer, $nested]),
                codecs: [$codec],
                limits: new AnonymizationLimits(maxInputBytes: $limit),
            );

            if ($withinLimit) {
                $anonymizer->anonymize('outer', ['payload' => $payload]);
                self::assertSame(1, $codec->decodeCalls);

                continue;
            }

            try {
                $anonymizer->anonymize('outer', ['payload' => $payload]);
                self::fail('Embedded payloads larger than the byte limit should be rejected.');
            } catch (ExecutionLimitExceeded) {
                self::assertSame(0, $codec->decodeCalls);
            }
        }
    }

    public function testNdjsonEnforcesLimitsPerRecordAndResetsBudgets(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            limits: new AnonymizationLimits(maxInputBytes: 13, maxVisitedNodes: 2),
        );
        $stream = $anonymizer->anonymizeNdjson('records', ["{\"email\":\"x\"}\r\n", '{"email":"x"}']);

        self::assertSame(
            ['{"email":"[REDACTED]"}', '{"email":"[REDACTED]"}'],
            iterator_to_array($stream->lines(), false),
        );
        self::assertSame(2, $stream->audit()->entries()[0]->count());

        $multibyteProfile = ProfileDefinition::fromArray('multibyte-records', [
            'mode' => 'preserve',
            'rules' => [['field' => 'x']],
            'root' => 'list',
        ]);
        $multibyteStream = new ArrayAnonymizer(
            new InMemoryProfileProvider([$multibyteProfile]),
            limits: new AnonymizationLimits(maxInputBytes: 11),
        )->anonymizeNdjson('multibyte-records', ["{\"x\":\"€\"}\n"]);

        self::assertSame(['{"x":"\u20ac"}'], iterator_to_array($multibyteStream->lines(), false));
    }

    public function testNdjsonRejectsOversizedRecordsLazilyAndLeavesAuditIncomplete(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            limits: new AnonymizationLimits(maxInputBytes: 13),
        );
        $oversizedFirst = $anonymizer->anonymizeNdjson('records', ['{"email":"xx"}']);

        try {
            iterator_to_array($oversizedFirst->lines(), false);
            self::fail('Oversized first record should fail only when pulled.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('maxInputBytes', $exception->getMessage());
            self::assertStringContainsString('record 0', $exception->getMessage());
        }
        $this->assertNdjsonAuditIsIncomplete($oversizedFirst);

        $oversizedLater = $anonymizer->anonymizeNdjson('records', ['{"email":"x"}', '{"email":"xx"}']);

        try {
            foreach ($oversizedLater->lines() as $output) {
                self::assertSame('{"email":"[REDACTED]"}', $output);
            }
            self::fail('Oversized later record should fail when iteration reaches it.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('record 1', $exception->getMessage());
        }
        $this->assertNdjsonAuditIsIncomplete($oversizedLater);

        $multibyteProfile = ProfileDefinition::fromArray('multibyte-records', [
            'mode' => 'preserve',
            'rules' => [['field' => 'x']],
            'root' => 'list',
        ]);
        $oversizedMultibyte = new ArrayAnonymizer(
            new InMemoryProfileProvider([$multibyteProfile]),
            limits: new AnonymizationLimits(maxInputBytes: 11),
        )->anonymizeNdjson('multibyte-records', ['{"x":"€"} ']);

        try {
            iterator_to_array($oversizedMultibyte->lines(), false);
            self::fail('A multibyte record one byte above the limit should fail.');
        } catch (ExecutionLimitExceeded) {
            $this->assertNdjsonAuditIsIncomplete($oversizedMultibyte);
        }
    }

    private function assertNdjsonAuditIsIncomplete(NdjsonAnonymizationResult $result): void
    {
        try {
            $result->audit();
        } catch (LogicException) {
            return;
        }

        self::fail('A failed or incomplete stream must not expose a completed audit.');
    }
}
