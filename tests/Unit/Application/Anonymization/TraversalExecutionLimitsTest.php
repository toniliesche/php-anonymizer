<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAuditResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
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
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\EmbeddedPayloadDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(ArrayTraversal::class)]
#[CoversClass(RuleTreeTraversal::class)]
#[CoversClass(ProfileDefinition::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(ExecutionLimitExceeded::class)]
#[UsesClass(InMemoryProfileProvider::class)]
#[UsesClass(AnonymizationContext::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactTraversalPolicy::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(DocumentAnonymizationResult::class)]
#[UsesClass(DocumentPayloadAnonymizationResult::class)]
#[UsesClass(NdjsonAnonymizationResult::class)]
#[UsesClass(NdjsonAuditResult::class)]
#[UsesClass(PayloadAnonymizationResult::class)]
#[UsesClass(ExecutionBudget::class)]
#[UsesClass(CompiledProfile::class)]
#[UsesClass(CompiledRuleList::class)]
#[UsesClass(RuleSelector::class)]
#[UsesClass(SelectedRule::class)]
#[UsesClass(SelectedValueDispatcher::class)]
#[UsesClass(TraversalExecutionContext::class)]
#[UsesClass(EmbeddedPayloadTraversal::class)]
#[UsesClass(JsonPayloadCodec::class)]
#[UsesClass(JsonPayloadData::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(CoreTransformationFactory::class)]
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileGlobalRuleParser::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass('PhpAnonymizer\Anonymizer\Application\Anonymization\NdjsonAnonymizationService')]
#[UsesClass('PhpAnonymizer\Anonymizer\Application\Anonymization\PayloadAnonymizationService')]
#[UsesClass('PhpAnonymizer\Anonymizer\Application\Anonymization\RuntimeAssembler')]
#[UsesClass('PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerRuntime')]
#[UsesClass('PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFacade')]
#[UsesClass(PayloadCodecFailure::class)]
#[UsesClass(ProfileNotFound::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(ProfileReferenceValidator::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(DateGeneralizeTransformation::class)]
#[UsesClass(NullifyTransformation::class)]
#[UsesClass(NumericBucketTransformation::class)]
#[UsesClass(PartialMaskTransformation::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(EmbeddedPayloadDefinitionParser::class)]
#[UsesClass(EmbeddedPayloadDefinition::class)]
#[UsesClass(RuleTraversalCombinationValidator::class)]
final class TraversalExecutionLimitsTest extends TestCase
{
    public function testDocumentListRootAndRecordNodesUseExactBudgets(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $input = '[{"email":"one"}]';
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            limits: new AnonymizationLimits(maxDepth: 2, maxVisitedNodes: 3, maxCollectionEntries: 1),
        );

        self::assertSame('[{"email":"[REDACTED]"}]', $anonymizer->anonymizeDocument('records', 'json', $input));

        foreach ([
            [new AnonymizationLimits(maxVisitedNodes: 2), '/0/email'],
            [new AnonymizationLimits(maxDepth: 1), '/0/email'],
        ] as [$limits, $pointer]) {
            try {
                new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), limits: $limits)
                    ->anonymizeDocument('records', 'json', $input);
                self::fail(sprintf('The list should exceed its budget at %s.', $pointer));
            } catch (ExecutionLimitExceeded $exception) {
                self::assertStringContainsString('profile "records"', $exception->getMessage());
                self::assertStringContainsString($pointer, $exception->getMessage());
            }
        }

        foreach ([
            ['[{"email":"one"},{"email":"two"}]', 'at ""'],
            ['[{"email":"one","name":"Ada"}]', '/0'],
        ] as [$document, $pointer]) {
            try {
                new ArrayAnonymizer(
                    new InMemoryProfileProvider([$profile]),
                    limits: new AnonymizationLimits(maxCollectionEntries: 1),
                )->anonymizeDocument('records', 'json', $document);
                self::fail(sprintf('The list or record collection should exceed its limit at %s.', $pointer));
            } catch (ExecutionLimitExceeded $exception) {
                self::assertStringContainsString('maxCollectionEntries', $exception->getMessage());
                self::assertStringContainsString($pointer, $exception->getMessage());
            }
        }
    }

    public function testDocumentMapRootUsesDepthZeroAndCountsRootOnce(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);
        $input = '{"email":"secret"}';

        self::assertSame('{"email":"[REDACTED]"}', new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            limits: new AnonymizationLimits(maxDepth: 1, maxVisitedNodes: 2),
        )->anonymizeDocument('customer', 'json', $input));

        try {
            new ArrayAnonymizer(
                new InMemoryProfileProvider([$profile]),
                limits: new AnonymizationLimits(maxDepth: 1, maxVisitedNodes: 1),
            )->anonymizeDocument('customer', 'json', $input);
            self::fail('The map root and selected leaf must both count as visited nodes.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('maxVisitedNodes', $exception->getMessage());
            self::assertStringContainsString('/email', $exception->getMessage());
        }

        $nested = ProfileDefinition::fromArray('nested-customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']]]],
        ]);

        try {
            new ArrayAnonymizer(
                new InMemoryProfileProvider([$nested]),
                limits: new AnonymizationLimits(maxDepth: 1),
            )->anonymizeDocument('nested-customer', 'json', '{"customer":{"email":"secret"}}');
            self::fail('A leaf below the child map must exceed the depth limit.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('maxDepth', $exception->getMessage());
            self::assertStringContainsString('/customer/email', $exception->getMessage());
        }
    }

    public function testSelectedStringListEnforcesDepthNodeAndCollectionBoundaries(): void
    {
        $profile = ProfileDefinition::fromArray('strings', [
            'mode' => 'redact',
            'rules' => [['field' => 'tags', 'each' => true]],
        ]);
        $input = ['tags' => ['one', 'two']];
        self::assertSame(['tags' => ['[REDACTED]', '[REDACTED]']], new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            limits: new AnonymizationLimits(maxDepth: 2, maxVisitedNodes: 4, maxCollectionEntries: 2),
        )->anonymize('strings', $input));

        foreach ([
            [new AnonymizationLimits(maxDepth: 1, maxVisitedNodes: 4, maxCollectionEntries: 2), 'maxDepth', '/tags/0'],
            [new AnonymizationLimits(maxDepth: 2, maxVisitedNodes: 3, maxCollectionEntries: 2), 'maxVisitedNodes', '/tags/1'],
            [new AnonymizationLimits(maxDepth: 2, maxVisitedNodes: 4, maxCollectionEntries: 1), 'maxCollectionEntries', '/tags'],
        ] as [$limits, $limit, $pointer]) {
            try {
                new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), limits: $limits)
                    ->anonymize('strings', $input);
                self::fail(sprintf('The selected string list should exceed %s at %s.', $limit, $pointer));
            } catch (ExecutionLimitExceeded $exception) {
                self::assertStringContainsString($limit, $exception->getMessage());
                self::assertStringContainsString($pointer, $exception->getMessage());
            }
        }
    }
}
