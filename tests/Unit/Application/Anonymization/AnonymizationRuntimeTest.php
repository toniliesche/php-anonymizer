<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerRuntime;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\NdjsonAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\PayloadAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactUnselectedValues;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ReplacementContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
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
use PhpAnonymizer\Anonymizer\Application\Exception\ContextRequired;
use PhpAnonymizer\Anonymizer\Application\Exception\ExecutionLimitExceeded;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
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
use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileGlobalRuleParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\EmbeddedPayloadDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticContextualReplacementStrategy;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticPayloadCodec;
use PhpAnonymizer\Anonymizer\Tests\Support\PullCountingIterable;
use PhpAnonymizer\Anonymizer\Tests\Support\Records;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(RuleTreeTraversal::class)]
#[CoversClass(AnonymizationLimits::class)]
#[CoversClass(ExecutionLimitExceeded::class)]
#[CoversClass(ExecutionBudget::class)]
#[CoversClass(AuditEntry::class)]
#[CoversClass(AuditAnonymizationResult::class)]
#[CoversClass(ContextRequired::class)]
#[CoversClass(AnonymizationContext::class)]
#[CoversClass(ReplacementContext::class)]
#[CoversClass(PreserveStringSelection::class)]
#[CoversClass(RedactTraversalPolicy::class)]
#[CoversClass(RuleOptionsCompiler::class)]
#[CoversClass(ProfileReferenceValidator::class)]
#[CoversClass(CoreTransformationFactory::class)]
#[CoversClass(DateGeneralizeTransformation::class)]
#[CoversClass(NullifyTransformation::class)]
#[CoversClass(NumericBucketTransformation::class)]
#[CoversClass(PartialMaskTransformation::class)]
#[CoversClass(TransformationRegistry::class)]
#[UsesClass(PayloadAnonymizationResult::class)]
#[UsesClass(InvalidProfileDefinition::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactUnselectedValues::class)]
#[CoversClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(ArrayTraversal::class)]
#[UsesClass(CompiledProfile::class)]
#[UsesClass(CompiledRuleList::class)]
#[UsesClass(RuleSelector::class)]
#[UsesClass(SelectedRule::class)]
#[UsesClass(EmbeddedPayloadTraversal::class)]
#[UsesClass(JsonPayloadCodec::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[CoversClass(ProfileDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
#[UsesClass(ProfileNotFound::class)]
#[UsesClass(AnonymizerRuntime::class)]
#[UsesClass(NdjsonAnonymizationService::class)]
#[UsesClass(PayloadAnonymizationService::class)]
#[UsesClass(RuntimeAssembler::class)]
#[UsesClass(SelectedValueDispatcher::class)]
#[UsesClass(TraversalExecutionContext::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(ProfileGlobalRuleParser::class)]
#[UsesClass(EmbeddedPayloadDefinitionParser::class)]
#[UsesClass(RuleTraversalCombinationValidator::class)]
final class AnonymizationRuntimeTest extends TestCase
{
    public function testNoReportPathReturnsSameTransformation(): void
    {
        $anonymizer = $this->engine();

        self::assertSame($anonymizer->anonymizeWithReport('customer', ['email' => 'secret'])->data(), $anonymizer->anonymize('customer', ['email' => 'secret']));
    }

    public function testContextIsPassedToAContextualStrategy(): void
    {
        $strategy = new StaticContextualReplacementStrategy(mode: 'describe');
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'contextual', 'key_id' => 'primary']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy]);

        self::assertSame(['email' => 'customer:/email:primary:acme'], $anonymizer->anonymize('customer', ['email' => 'secret'], new AnonymizationContext(['tenant' => 'acme'])));
    }

    public function testContextProvidesAStableStringPathAlongsideTheLegacyPointer(): void
    {
        $context = new ReplacementContext('customer', JsonPointer::fromString('/email'), 'primary', new AnonymizationContext());

        self::assertSame('/email', $context->pathString());
        self::assertSame('/email', (string) $context->path());
    }

    public function testContextualStrategyRequiresContext(): void
    {
        $strategy = new StaticContextualReplacementStrategy();
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'contextual', 'key_id' => 'primary']]]);
        $this->expectException(ContextRequired::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy])->anonymize('customer', ['email' => 'secret']);
    }

    public function testContextualReportPayloadBatchAndAuditOperations(): void
    {
        $strategy = new StaticContextualReplacementStrategy(mode: 'key', fixed: 'safe-');
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'contextual', 'key_id' => 'primary']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy]);
        $context = new AnonymizationContext(['tenant' => 'acme']);

        self::assertSame(['email' => 'safe-primary'], $anonymizer->anonymizeWithReport('customer', ['email' => 'secret'], $context)->data());
        self::assertSame('{"email":"safe-primary"}', $anonymizer->anonymizePayload('customer', 'json', '{"email":"secret"}', $context));
        self::assertCount(1, iterator_to_array($anonymizer->anonymizeRecords('customer', [['email' => 'secret']], $context), false));
        self::assertSame(['contextual'], array_map(static fn (AuditEntry $entry): string => (string) $entry->handler(), $anonymizer->anonymizeWithAudit('customer', ['email' => 'secret'], $context)->entries()));

        try {
            iterator_to_array($anonymizer->anonymizeRecords('customer', [['secret']], $context), false);
            self::fail('Expected invalid batch record.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(InvalidArgumentException::class, $exception::class);
        }
    }

    public function testContextualStrategyWorksInPreserveMode(): void
    {
        $strategy = new StaticContextualReplacementStrategy(mode: 'fixed', fixed: 'pseudonym');
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'preserve', 'rules' => [['field' => 'email', 'strategy' => 'contextual', 'key_id' => 'primary']]]);
        self::assertSame(['email' => 'pseudonym'], new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy])->anonymize('customer', ['email' => 'secret'], new AnonymizationContext()));

        $this->expectException(ContextRequired::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy])->anonymize('customer', ['email' => 'secret']);
    }

    public function testContextAndKeyIdValidation(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [['field' => 'email', 'key_id' => 'primary']]]);
    }

    public function testContextRejectsInvalidAttributesAndBatchRejectsInvalidRecords(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AnonymizationContext([1 => 'invalid']);
    }

    public function testContextRejectsStringKeysWithNonStringValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AnonymizationContext(['tenant' => 42]);
    }

    public function testContextAttributesAreNormalizedInStableOrder(): void
    {
        self::assertSame(['alpha' => 'one', 'zeta' => 'two'], new AnonymizationContext(['zeta' => 'two', 'alpha' => 'one'])->attributes());
    }

    public function testKeyIdMustBeNonEmptyString(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'redact', 'key_id' => 7]]]);
    }

    public function testKeyIdRequiresContextualStrategy(): void
    {
        $profile = ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'redact', 'key_id' => 'primary']]]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('non-contextual strategy "redact"');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
    }

    public function testContextualStrategyRequiresKeyId(): void
    {
        $strategy = new StaticContextualReplacementStrategy();
        $profile = ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'contextual']]]);
        $this->expectException(InvalidArgumentException::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy]);
    }

    public function testBatchIsLazyAndProcessesRecordsIndependently(): void
    {
        $anonymizer = $this->engine();
        $records = new Records([['email' => 'one'], ['email' => 'two']]);

        self::assertSame([
            ['email' => '[REDACTED]'],
            ['email' => '[REDACTED]'],
        ], iterator_to_array($anonymizer->anonymizeRecords('customer', $records), false));
    }

    public function testBatchDoesNotPullSourceBeforeIterationStarts(): void
    {
        $records = new PullCountingIterable([['email' => 'one']]);

        $stream = $this->engine()->anonymizeRecords('customer', $records);

        self::assertSame(0, $records->pulls());
        iterator_to_array($stream, false);
        self::assertSame(1, $records->pulls());
    }

    public function testBatchRejectsListRecords(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array($this->engine()->anonymizeRecords('customer', [['secret']]), false);
    }

    public function testBatchRejectsInvalidRecordWithItsStringKeyInTheMessage(): void
    {
        $records = $this->engine()->anonymizeRecords('customer', ['customer-record' => 'not a map']);

        $this->expectExceptionMessageIsOrContains('Batch record customer-record must be an associative array.');
        iterator_to_array($records);
    }

    public function testBatchContextVariantRejectsInvalidRecordWithItsStringKeyInTheMessage(): void
    {
        $records = $this->engine()->anonymizeRecords('customer', ['customer-record' => 'not a map'], new AnonymizationContext());

        $this->expectExceptionMessageIsOrContains('Batch record customer-record must be an associative array.');
        iterator_to_array($records);
    }

    public function testLimitsIncludeCurrentPointer(): void
    {
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([ProfileDefinition::fromArray('customer', [
                'mode' => 'redact', 'rules' => [['field' => 'email']],
            ])]),
            limits: new AnonymizationLimits(maxVisitedNodes: 1),
        );

        $this->expectException(ExecutionLimitExceeded::class);
        $this->expectExceptionMessageMatches('/email/');
        $anonymizer->anonymize('customer', ['email' => 'secret']);
    }

    public function testTraversalCountsNestedVisitsAndCollectionsAgainstLimits(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'items', 'each' => [['field' => 'email']]]],
        ]);
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            limits: new AnonymizationLimits(maxCollectionEntries: 1),
        );

        $this->expectException(ExecutionLimitExceeded::class);
        $this->expectExceptionMessageMatches('/maxCollectionEntries.*\/items/');
        $anonymizer->anonymize('nested', ['items' => [['email' => 'one'], ['email' => 'two']]]);
    }

    public function testTraversalCountsNestedDepthAgainstLimits(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']]]],
        ]);
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            limits: new AnonymizationLimits(maxDepth: 1),
        );

        $this->expectException(ExecutionLimitExceeded::class);
        $this->expectExceptionMessageMatches('/maxDepth.*\/customer/');
        $anonymizer->anonymize('nested', ['customer' => ['email' => 'secret']]);
    }

    public function testTraversalCountsEmptyChildrenAndEachMapsAtTheirDepth(): void
    {
        foreach ([
            [['field' => 'customer', 'children' => [['field' => 'email']]], ['customer' => []]],
            [['field' => 'items', 'each' => [['field' => 'email']]], ['items' => [[]]]],
        ] as [$rule, $data]) {
            $profile = ProfileDefinition::fromArray('nested', ['mode' => 'redact', 'rules' => [$rule]]);
            $anonymizer = new ArrayAnonymizer(
                new InMemoryProfileProvider([$profile]),
                limits: new AnonymizationLimits(maxDepth: 1),
            );

            try {
                $anonymizer->anonymize('nested', $data);
                self::fail('An empty nested map beyond maxDepth should be rejected.');
            } catch (ExecutionLimitExceeded $exception) {
                self::assertStringContainsString('maxDepth', $exception->getMessage());
            }
        }
    }

    public function testTraversalBudgetsCoverStringListsRootMapsAndEmptyUnselectedMaps(): void
    {
        $plain = ProfileDefinition::fromArray('plain', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $limited = new ArrayAnonymizer(
            new InMemoryProfileProvider([$plain]),
            limits: new AnonymizationLimits(maxCollectionEntries: 1),
        );

        try {
            $limited->anonymize('plain', ['email' => 'one', 'name' => 'two']);
            self::fail('The root map should count every entry against the collection budget.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('maxCollectionEntries', $exception->getMessage());
        }

        $strings = ProfileDefinition::fromArray('strings', [
            'mode' => 'preserve',
            'rules' => [['field' => 'tags', 'each' => true]],
        ]);

        try {
            new ArrayAnonymizer(
                new InMemoryProfileProvider([$strings]),
                limits: new AnonymizationLimits(maxCollectionEntries: 1),
            )->anonymize('strings', ['tags' => ['one', 'two']]);
            self::fail('Selected string lists should count their entries against the collection budget.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('/tags', $exception->getMessage());
        }

        try {
            new ArrayAnonymizer(
                new InMemoryProfileProvider([$plain]),
                limits: new AnonymizationLimits(maxVisitedNodes: 1),
            )->anonymize('plain', ['metadata' => []]);
            self::fail('An empty unselected map should still count as a visited node.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('/metadata', $exception->getMessage());
        }
    }

    public function testTraversalCountsDepthAcrossUnselectedMapsAndDoesNotReapplyUnselectedLists(): void
    {
        $preserve = ProfileDefinition::fromArray('preserve', [
            'mode' => 'preserve',
            'rules' => [['field' => 'email']],
        ]);

        try {
            new ArrayAnonymizer(
                new InMemoryProfileProvider([$preserve]),
                limits: new AnonymizationLimits(maxDepth: 1),
            )->anonymize('preserve', ['metadata' => ['nested' => ['value' => 'secret']]]);
            self::fail('Deep unselected maps should still count toward maxDepth.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('/metadata/nested', $exception->getMessage());
        }

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$preserve]))
            ->anonymizeWithReport('preserve', ['tags' => ['public', 'internal']]);
        self::assertSame(['tags' => ['[REDACTED]', '[REDACTED]']], $result->data());
        self::assertSame([
            ['path' => '/email', 'action' => 'missing', 'handler' => null],
            ['path' => '/tags/0', 'action' => 'replace', 'handler' => 'redact'],
            ['path' => '/tags/1', 'action' => 'replace', 'handler' => 'redact'],
        ], $result->events());
    }

    public function testTraversalReusesAnExecutionBudgetSuppliedByTheCaller(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $traversal = new ArrayTraversal(
            new ProfileRegistry([$profile]),
            new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]),
            new PayloadCodecRegistry([new JsonPayloadCodec()]),
        );
        $budget = new ExecutionBudget(new AnonymizationLimits(maxVisitedNodes: 3));

        $traversal->apply('customer', ['email' => 'one'], budget: $budget);
        $this->expectException(ExecutionLimitExceeded::class);
        $traversal->apply('customer', ['email' => 'two'], budget: $budget);
    }

    public function testBudgetAcceptsValuesWithinAllLimits(): void
    {
        $budget = new ExecutionBudget(new AnonymizationLimits());
        $path = JsonPointer::root()->append('email');
        $budget->visit('customer', $path, 1);
        $budget->collection('customer', $path, 1);
        $budget->inputBytes('customer', (string) $path, 1);
        $budget->enterEmbedded('customer', $path);
        $budget->leaveEmbedded();
        self::assertSame('/email', (string) $path);
    }

    public function testBudgetRejectsDepthCollectionAndInputLimits(): void
    {
        $path = JsonPointer::root()->append('email');
        $budget = new ExecutionBudget(new AnonymizationLimits(maxInputBytes: 1, maxCollectionEntries: 1));

        try {
            $budget->collection('customer', $path, 2);
            self::fail('Expected collection limit.');
        } catch (ExecutionLimitExceeded) {
        }

        try {
            $budget->inputBytes('customer', (string) $path, 2);
            self::fail('Expected input limit.');
        } catch (ExecutionLimitExceeded) {
        }
        $this->expectException(ExecutionLimitExceeded::class);
        new ExecutionBudget(new AnonymizationLimits(maxDepth: 1))->visit('customer', $path, 2);
    }

    public function testBudgetRejectsEmbeddedDepthAndInputLimits(): void
    {
        $path = JsonPointer::root()->append('payload');
        $budget = new ExecutionBudget(new AnonymizationLimits(maxInputBytes: 1, maxEmbeddedPayloadDepth: 1));
        $budget->enterEmbedded('customer', $path);

        try {
            $budget->inputBytes('customer', (string) $path, 2);
            self::fail('Expected embedded input limit.');
        } catch (ExecutionLimitExceeded) {
        }
        $this->expectException(ExecutionLimitExceeded::class);
        $budget->enterEmbedded('customer', $path);
    }

    public function testBudgetAcceptsExactConfiguredBoundariesAndRestoresEmbeddedDepth(): void
    {
        $path = JsonPointer::root()->append('payload');
        $budget = new ExecutionBudget(new AnonymizationLimits(
            maxInputBytes: 2,
            maxDepth: 2,
            maxVisitedNodes: 2,
            maxCollectionEntries: 2,
            maxEmbeddedPayloadDepth: 2,
        ));

        $budget->visit('customer', $path, 2);
        $budget->visit('customer', $path, 2);
        $budget->collection('customer', $path, 2);
        $budget->inputBytes('customer', (string) $path, 2);
        $budget->enterEmbedded('customer', $path);
        $budget->enterEmbedded('customer', $path);
        $budget->leaveEmbedded();
        $budget->enterEmbedded('customer', $path);
        self::assertSame('/payload', (string) $path);
    }

    public function testAuditAggregatesWithoutValues(): void
    {
        $result = $this->engine()->anonymizeWithAudit('customer', ['email' => 'secret']);

        self::assertSame('[REDACTED]', $result->data()['email']);
        self::assertCount(1, $result->entries());
        self::assertSame('customer', $result->entries()[0]->profile());
        self::assertNotSame('', $result->entries()[0]->fingerprint());
        self::assertSame('/email', $result->entries()[0]->path());
        self::assertSame('replace', $result->entries()[0]->action());
        self::assertSame('redact', $result->entries()[0]->handler());
        self::assertSame(1, $result->entries()[0]->count());
        self::assertStringNotContainsString('secret', serialize($result->entries()));
    }

    public function testAuditKeepsDistinctPathsAndSortsEntries(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'zeta'], ['field' => 'alpha']],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithAudit('customer', ['zeta' => 'one', 'alpha' => 'two']);

        self::assertSame(['/alpha', '/zeta'], array_map(static fn (AuditEntry $entry): string => $entry->path(), $result->entries()));
        self::assertSame([1, 1], array_map(static fn (AuditEntry $entry): int => $entry->count(), $result->entries()));
    }

    public function testLimitsRejectNonPositiveValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AnonymizationLimits(maxDepth: 0);
    }

    public function testAuditRejectsUnknownProfile(): void
    {
        $this->expectException(ProfileNotFound::class);
        $this->engine()->anonymizeWithAudit('missing', []);
    }

    public function testPayloadInputLimitIsReportedDirectly(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), limits: new AnonymizationLimits(maxInputBytes: 1));

        $this->expectException(ExecutionLimitExceeded::class);
        $anonymizer->anonymizePayload('customer', 'json', '{"email":"secret"}');
    }

    public function testPayloadInputLimitUsesUtf8CharacterLength(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $codec = new StaticPayloadCodec('unicode', []);
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            codecs: [$codec],
            limits: new AnonymizationLimits(maxInputBytes: 1),
        );

        self::assertSame('', $anonymizer->anonymizePayload('customer', 'unicode', '€'));
    }

    public function testPreserveScalarRedactionIsAuditedWithRedactHandler(): void
    {
        $profile = ProfileDefinition::fromArray('preserve', [
            'mode' => 'preserve',
            'rules' => [['field' => 'email', 'children' => [['field' => 'value']], 'on_scalar' => 'redact']],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymizeWithAudit('preserve', ['email' => 'secret']);

        self::assertSame('redact', $result->entries()[0]->handler());
    }

    public function testDisabledReportDropsAllKindsOfEvents(): void
    {
        $report = AnonymizationReport::disabled();
        $path = JsonPointer::root()->append('secret');
        $report->anonymized($path, 'replace', 'redact');
        $report->missing($path);
        $report->event($path, 'custom', 'handler');

        self::assertFalse($report->isCollecting());
        self::assertSame([], $report->anonymizedPaths());
        self::assertSame([], $report->missingPaths());
        self::assertSame([], $report->events());
    }

    public function testCollectingReportRecordsNullableHandlersAndPreservesEventOrder(): void
    {
        $report = AnonymizationReport::collecting();
        $first = JsonPointer::root()->append('first');
        $second = JsonPointer::root()->append('second');

        $report->event($first, 'observe');
        $report->anonymized($second, 'transform');
        $report->missing($first);

        self::assertTrue($report->isCollecting());
        self::assertSame(['/second'], $report->anonymizedPaths());
        self::assertSame(['/first'], $report->missingPaths());
        self::assertSame([
            ['path' => '/first', 'action' => 'observe', 'handler' => null],
            ['path' => '/second', 'action' => 'transform', 'handler' => null],
            ['path' => '/first', 'action' => 'missing', 'handler' => null],
        ], $report->events());
    }

    public function testReportMergePrefixesAllPathsAndPreservesEventMetadata(): void
    {
        $child = AnonymizationReport::collecting();
        $child->anonymized(JsonPointer::root()->append('email'), 'replace', 'redact');
        $child->missing(JsonPointer::root()->append('name'));
        $child->event(JsonPointer::root()->append('custom'), 'observe');
        $result = new AnonymizationResult(['email' => '[REDACTED]'], $child->anonymizedPaths(), $child->missingPaths(), $child->events());

        $parent = AnonymizationReport::collecting();
        $parent->merge($result, JsonPointer::root()->append('user'));

        self::assertSame(['/user/email'], $parent->anonymizedPaths());
        self::assertSame(['/user/name'], $parent->missingPaths());
        self::assertSame([
            ['path' => '/user/email', 'action' => 'replace', 'handler' => 'redact'],
            ['path' => '/user/name', 'action' => 'missing', 'handler' => null],
            ['path' => '/user/custom', 'action' => 'observe', 'handler' => null],
        ], $parent->events());
    }

    private function engine(): ArrayAnonymizer
    {
        return new ArrayAnonymizer(new InMemoryProfileProvider([
            ProfileDefinition::fromArray('customer', [
                'mode' => 'redact', 'rules' => [['field' => 'email']],
            ]),
        ]));
    }
}
