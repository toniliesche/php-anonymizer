<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFacade;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerRuntime;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\NdjsonAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ObjectAnonymizationRequest;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\PayloadAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactUnselectedValues;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
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
use PhpAnonymizer\Anonymizer\Application\Exception\ObjectAnonymizationUnavailable;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
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
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticPayloadCodec;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticReplacementStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(AnonymizationLimits::class)]
#[CoversClass(ExecutionBudget::class)]
#[CoversClass(AnonymizationResult::class)]
#[CoversClass(ProfileNotFound::class)]
#[CoversClass(UnsupportedValue::class)]
#[CoversClass(AnonymizationReport::class)]
#[CoversClass(RedactTraversalPolicy::class)]
#[CoversClass(PreserveTraversalPolicy::class)]
#[CoversClass(RuleTreeTraversal::class)]
#[UsesClass(CompiledProfile::class)]
#[UsesClass(CompiledRuleList::class)]
#[UsesClass(RuleSelector::class)]
#[UsesClass(SelectedRule::class)]
#[CoversClass(RedactUnselectedValues::class)]
#[CoversClass(ArrayTraversal::class)]
#[CoversClass(EmbeddedPayloadTraversal::class)]
#[CoversClass(PreserveStringSelection::class)]
#[CoversClass(PayloadAnonymizationResult::class)]
#[CoversClass(PayloadCodecFailure::class)]
#[CoversClass(ExecutionLimitExceeded::class)]
#[CoversClass(PayloadCodecRegistry::class)]
#[CoversClass(PayloadAnonymizationService::class)]
#[CoversClass(ProfileRegistry::class)]
#[CoversClass(ReplacementStrategyRegistry::class)]
#[CoversClass(DefaultRedactionStrategy::class)]
#[CoversClass(StarMaskStrategy::class)]
#[CoversClass(JsonPayloadCodec::class)]
#[CoversClass(CoreTransformationFactory::class)]
#[CoversClass(DateGeneralizeTransformation::class)]
#[CoversClass(NullifyTransformation::class)]
#[CoversClass(NumericBucketTransformation::class)]
#[CoversClass(PartialMaskTransformation::class)]
#[CoversClass(TransformationRegistry::class)]
#[UsesClass(ConditionDefinition::class)]
#[UsesClass(ConditionDefinitionParser::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(EmbeddedPayloadDefinitionParser::class)]
#[UsesClass(RuleTraversalCombinationValidator::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(EmbeddedPayloadDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(ProfileReferenceValidator::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(NdjsonAnonymizationService::class)]
#[UsesClass(AnonymizationContext::class)]
#[CoversClass(ObjectAnonymizationRequest::class)]
#[UsesClass(ObjectAnonymizationUnavailable::class)]
#[CoversClass(RuntimeAssembler::class)]
#[CoversClass(SelectedValueDispatcher::class)]
#[CoversClass(TraversalExecutionContext::class)]
#[UsesClass(AnonymizerRuntime::class)]
#[UsesClass(AnonymizerFacade::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(ProfileGlobalRuleParser::class)]
final class ArrayAnonymizerTest extends TestCase
{
    private ArrayAnonymizer $anonymizer;

    #[Override]
    protected function setUp(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email'], ['field' => 'token']],
        ]);
        $this->anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
    }

    public function testRedactsSelectedStringsAndPreservesOtherValues(): void
    {
        $data = ['email' => 'a@example.com', 'token' => '', 'name' => 'Ada', 'age' => 42];

        self::assertSame(['email' => '[REDACTED]', 'token' => '[REDACTED]', 'name' => 'Ada', 'age' => 42], $this->anonymizer->anonymize('customer', $data));
    }

    public function testLeavesMissingFieldsUntouched(): void
    {
        self::assertSame(['name' => 'Ada'], $this->anonymizer->anonymize('customer', ['name' => 'Ada']));
    }

    public function testDoesNotMutateInput(): void
    {
        $data = ['email' => 'a@example.com'];
        $original = $data;
        $this->anonymizer->anonymize('customer', $data);

        // @phpstan-ignore staticMethod.alreadyNarrowedType (The assertion documents input immutability.)
        self::assertSame($original, $data);
    }

    public function testReportsUnknownProfile(): void
    {
        $this->expectException(ProfileNotFound::class);
        $this->anonymizer->anonymize('missing', []);
    }

    public function testAuditRejectsUnknownProfileBeforeTraversing(): void
    {
        $this->expectException(ProfileNotFound::class);
        $this->anonymizer->anonymizeWithAudit('missing', []);
    }

    public function testRejectsSelectedNonStringValues(): void
    {
        $this->expectException(UnsupportedValue::class);
        $this->expectExceptionMessageMatches('/customer/');
        $this->anonymizer->anonymize('customer', ['email' => ['nested' => true]]);
    }

    public function testTraversesNestedMapsAndListsAndReportsChangesAndMissingPaths(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'customer', 'children' => [['field' => 'email']]],
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $result = $anonymizer->anonymizeWithReport('nested', [
            'customer' => ['email' => 'a@example.com'],
            'orders' => [['reference' => 'one'], []],
            'tags' => ['internal', 'public'],
        ]);

        self::assertSame([
            'customer' => ['email' => '[REDACTED]'],
            'orders' => [['reference' => '[REDACTED]'], []],
            'tags' => ['[REDACTED]', '[REDACTED]'],
        ], $result->data());
        self::assertSame(['/customer/email', '/orders/0/reference', '/tags/0', '/tags/1'], $result->anonymizedPaths());
        self::assertSame(['/orders/1/reference'], $result->missingPaths());
    }

    public function testReportsMissingContainerOnlyOnce(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']]]],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('nested', []);

        self::assertSame(['/customer'], $result->missingPaths());
    }

    public function testRejectsWrongNestedShapeWithJsonPointer(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']]]],
        ]);

        $this->expectException(UnsupportedValue::class);
        $this->expectExceptionMessageMatches('#/customer#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('nested', ['customer' => ['unexpected']]);
    }

    public function testEscapesJsonPointerSegments(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'a/b~c']],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('nested', ['a/b~c' => 'secret']);

        self::assertSame(['/a~1b~0c'], $result->anonymizedPaths());
    }

    public function testAcceptsEmptyNestedMapsAndLists(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'customer', 'children' => [['field' => 'email']]],
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('nested', ['customer' => [], 'orders' => [], 'tags' => []]);

        self::assertSame(['/customer/email'], $result->missingPaths());
    }

    public function testRejectsWrongListAndScalarElementShapes(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $this->expectExceptionMessageMatches('#/orders#');
        $anonymizer->anonymize('nested', ['orders' => ['not-a-map'], 'tags' => []]);
    }

    public function testRejectsNonStringScalarListElement(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'tags', 'each' => true]],
        ]);

        $this->expectExceptionMessageMatches('#/tags/0#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('nested', ['tags' => [42]]);
    }

    public function testRejectsNonListForEach(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'tags', 'each' => true]],
        ]);

        $this->expectExceptionMessageMatches('#/tags#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('nested', ['tags' => ['name' => 'value']]);
    }

    public function testRejectsNonListForEachMapRules(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'orders', 'each' => [['field' => 'reference']]]],
        ]);

        $this->expectExceptionMessageMatches('#/orders#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('nested', ['orders' => ['first' => ['reference' => 'A-1']]]);
    }

    public function testPreserveModeKeepsSelectedValuesAndRedactsAllOtherStrings(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [
                ['field' => 'email'],
                ['field' => 'customer', 'children' => [['field' => 'name']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', [
                'email' => 'keep@example.com',
                'name' => 'hide',
                'customer' => ['name' => 'Keep', 'city' => 'Hide'],
                'tags' => ['one', 'two'],
                'nested' => ['secret' => 'hide'],
                'count' => 3,
            ]);

        self::assertSame([
            'email' => 'keep@example.com',
            'name' => '[REDACTED]',
            'customer' => ['name' => 'Keep', 'city' => '[REDACTED]'],
            'tags' => ['one', 'two'],
            'nested' => ['secret' => '[REDACTED]'],
            'count' => 3,
        ], $result->data());
        self::assertSame(['/name', '/customer/city', '/nested/secret'], $result->anonymizedPaths());
    }

    public function testConditionsUseOriginalSiblingValuesAndSkipNonMatches(): void
    {
        $profile = ProfileDefinition::fromArray('conditional', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'kind'],
                ['field' => 'email', 'when' => ['path' => 'kind', 'equals' => 'person']],
                ['field' => 'phone', 'when' => ['path' => 'kind', 'equals' => 'company']],
            ],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('conditional', [
                'kind' => 'person',
                'email' => 'a@example.com',
                'phone' => '0123',
            ]);

        self::assertSame('[REDACTED]', $result->data()['email']);
        self::assertSame('0123', $result->data()['phone']);
        self::assertSame(['/kind', '/email'], $result->anonymizedPaths());
        self::assertSame([], $result->missingPaths());
    }

    public function testScalarActionOverridesProfileMode(): void
    {
        $redact = ProfileDefinition::fromArray('redact-fallback', [
            'mode' => 'redact',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'preserve']],
        ]);
        $preserve = ProfileDefinition::fromArray('preserve-fallback', [
            'mode' => 'preserve',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'redact']],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$redact, $preserve]));

        self::assertSame(['address' => 'private'], $anonymizer->anonymize('redact-fallback', ['address' => 'private']));
        self::assertSame(['address' => '[REDACTED]'], $anonymizer->anonymize('preserve-fallback', ['address' => 'private']));
    }

    public function testPreserveModeReportsMissingSelectedFields(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'email'], ['field' => 'phone', 'when' => ['path' => 'kind', 'equals' => 'person']]],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['kind' => 'person']);

        self::assertSame(['/email', '/phone'], $result->missingPaths());
        self::assertSame(['/kind'], $result->anonymizedPaths());
    }

    public function testPreserveModeSkipsMissingConditionalRulesWhenPredicateDoesNotMatch(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'phone', 'when' => ['path' => 'kind', 'equals' => 'person']]],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['kind' => 'company']);

        self::assertSame(['/kind'], $result->anonymizedPaths());
        self::assertSame([], $result->missingPaths());
    }

    public function testPreserveModeTraversesListsOfSelectedMaps(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'orders', 'each' => [['field' => 'reference']]]],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['orders' => [
                ['reference' => 'keep', 'customer' => 'hide'],
            ]]);

        self::assertSame(['orders' => [['reference' => 'keep', 'customer' => '[REDACTED]']]], $result->data());
    }

    public function testPreserveModeRejectsSelectedInvalidValues(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [
                ['field' => 'email'],
                ['field' => 'address', 'children' => [['field' => 'street']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $this->expectException(UnsupportedValue::class);
        $anonymizer->anonymize('allow', ['email' => 42, 'address' => [], 'tags' => []]);
    }

    public function testPreserveModeRejectsSelectedLeafArray(): void
    {
        $profile = ProfileDefinition::fromArray('allow', ['mode' => 'preserve', 'rules' => [['field' => 'email']]]);

        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('allow', ['email' => []]);
    }

    public function testPreserveModeRejectsInvalidSelectedLists(): void
    {
        $strings = ProfileDefinition::fromArray('strings', ['mode' => 'preserve', 'rules' => [['field' => 'tags', 'each' => true]]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$strings]));
        $this->expectException(UnsupportedValue::class);
        $anonymizer->anonymize('strings', ['tags' => [42]]);
    }

    public function testPreserveModeRejectsInvalidSelectedMapLists(): void
    {
        $maps = ProfileDefinition::fromArray('maps', ['mode' => 'preserve', 'rules' => [['field' => 'orders', 'each' => [['field' => 'id']]]]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$maps]));
        $this->expectException(UnsupportedValue::class);
        $anonymizer->anonymize('maps', ['orders' => ['id' => 'not-a-list']]);
    }

    public function testRedactScalarFallbackRejectsNonStringValues(): void
    {
        $profile = ProfileDefinition::fromArray('fallback', [
            'mode' => 'redact',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'preserve']],
        ]);

        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('fallback', ['address' => 42]);
    }

    public function testPreserveModeRejectsNonMapListElement(): void
    {
        $profile = ProfileDefinition::fromArray('maps', ['mode' => 'preserve', 'rules' => [['field' => 'orders', 'each' => [['field' => 'id']]]]]);

        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('maps', ['orders' => [42]]);
    }

    public function testRedactScalarActionCanRedactMixedShapeString(): void
    {
        $profile = ProfileDefinition::fromArray('fallback', [
            'mode' => 'redact',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'redact']],
        ]);

        self::assertSame(['address' => '[REDACTED]'], new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('fallback', ['address' => 'private']));
    }

    public function testPreserveModeRejectsContainerAndListShapeMismatches(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [
                ['field' => 'address', 'children' => [['field' => 'street']]],
                ['field' => 'tags', 'each' => true],
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            $anonymizer->anonymize('allow', ['address' => ['street'], 'tags' => [], 'orders' => []]);
            self::fail('Expected a selected map shape exception.');
        } catch (UnsupportedValue) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(UnsupportedValue::class);
        $anonymizer->anonymize('allow', ['address' => [], 'tags' => ['name' => 'value'], 'orders' => []]);
    }

    public function testPreserveModeRejectsScalarWithoutExplicitFallback(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']]]],
        ]);

        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('allow', ['address' => 'private']);
    }

    public function testPreserveModeRejectsScalarWithoutFallbackForEachRules(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'orders', 'each' => [['field' => 'id']]]],
        ]);

        $this->expectExceptionMessageMatches('#/orders#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('allow', ['orders' => 'private']);
    }

    public function testPreserveModeRejectsScalarWithoutFallbackForStringLists(): void
    {
        $profile = ProfileDefinition::fromArray('allow', ['mode' => 'preserve', 'rules' => [['field' => 'tags', 'each' => true]]]);

        $this->expectExceptionMessageMatches('#/tags#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('allow', ['tags' => 'private']);
    }

    public function testPreserveModeReportsPreciseListErrorPaths(): void
    {
        $strings = ProfileDefinition::fromArray('strings', ['mode' => 'preserve', 'rules' => [['field' => 'tags', 'each' => true]]]);

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$strings]))->anonymize('strings', ['tags' => [42]]);
            self::fail('Expected a list element error.');
        } catch (UnsupportedValue $exception) {
            self::assertStringContainsString('/tags/0', $exception->getMessage());
        }

        $maps = ProfileDefinition::fromArray('maps', ['mode' => 'preserve', 'rules' => [['field' => 'orders', 'each' => [['field' => 'id']]]]]);

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$maps]))->anonymize('maps', ['orders' => [42]]);
            self::fail('Expected a map element error.');
        } catch (UnsupportedValue $exception) {
            self::assertStringContainsString('/orders/0', $exception->getMessage());
        }
    }

    public function testPreserveModeEscapesUnselectedPaths(): void
    {
        $profile = ProfileDefinition::fromArray('allow', ['mode' => 'preserve', 'rules' => [['field' => 'email']]]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['email' => 'keep', 'a/b~c' => 'hide']);

        self::assertSame(['/a~1b~0c'], $result->anonymizedPaths());
    }

    public function testNestedChildrenAndEachDispatchProduceExactIndexedPaths(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [[
                'field' => 'account',
                'children' => [[
                    'field' => 'contacts',
                    'each' => [['field' => 'email_address']],
                ]],
            ]],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('nested', [
                'account' => ['contacts' => [
                    ['email_address' => 'one'],
                    ['email_address' => 'two'],
                ]],
            ]);

        self::assertSame([
            'account' => ['contacts' => [
                ['email_address' => '[REDACTED]'],
                ['email_address' => '[REDACTED]'],
            ]],
        ], $result->data());
        self::assertSame(['/account/contacts/0/email_address', '/account/contacts/1/email_address'], $result->anonymizedPaths());
    }

    public function testTopLevelJsonPayloadIsAnonymizedAndReported(): void
    {
        $profile = ProfileDefinition::fromArray('payload', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
        $payload = '{"email":"secret","name":"Ada"}';

        $result = $anonymizer->anonymizePayloadWithReport('payload', 'json', $payload);

        self::assertSame('{"email":"[REDACTED]","name":"Ada"}', $result->payload());
        self::assertSame(['/email'], $result->anonymizationResult()->anonymizedPaths());
    }

    public function testEmbeddedJsonPrefixesNestedReportPaths(): void
    {
        $nested = ProfileDefinition::fromArray('nested', ['mode' => 'redact', 'rules' => [['field' => 'email'], ['field' => 'missing']]]);
        $outer = ProfileDefinition::fromArray('outer', [
            'mode' => 'redact',
            'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'nested']]],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$nested, $outer]))
            ->anonymizeWithReport('outer', ['payload' => '{"email":"secret"}']);

        self::assertSame(['payload' => '{"email":"[REDACTED]"}'], $result->data());
        self::assertSame(['/payload/email'], $result->anonymizedPaths());
        self::assertSame(['/payload/missing'], $result->missingPaths());
        self::assertSame([
            ['path' => '/payload', 'action' => 'embedded', 'handler' => 'json'],
            ['path' => '/payload/missing', 'action' => 'missing', 'handler' => null],
            ['path' => '/payload/email', 'action' => 'replace', 'handler' => 'redact'],
        ], $result->events());
    }

    public function testEmbeddedPayloadUsesUtf8LengthAndPreservesTheCallerBudget(): void
    {
        $nested = ProfileDefinition::fromArray('nested', ['mode' => 'redact', 'rules' => [['field' => 'payload']]]);
        $outer = ProfileDefinition::fromArray('outer', [
            'mode' => 'redact',
            'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'plain', 'profile' => 'nested']]],
        ]);
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$nested, $outer]),
            codecs: [new StaticPayloadCodec('plain')],
            limits: new AnonymizationLimits(maxInputBytes: 1),
        );

        self::assertSame(['payload' => '[REDACTED]'], $anonymizer->anonymize('outer', ['payload' => '€']));
    }

    public function testEmbeddedCodecRejectsListRootsBeforeNestedTraversal(): void
    {
        $nested = ProfileDefinition::fromArray('nested', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $outer = ProfileDefinition::fromArray('outer', [
            'mode' => 'redact',
            'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'list', 'profile' => 'nested']]],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Expected map - associative array with string keys.');
        new ArrayAnonymizer(
            new InMemoryProfileProvider([$nested, $outer]),
            codecs: [new StaticPayloadCodec('list', [0 => 'not-a-map'])],
        )->anonymize('outer', ['payload' => 'encoded']);
    }

    public function testPreserveModeSupportsEmbeddedJson(): void
    {
        $nested = ProfileDefinition::fromArray('nested', ['mode' => 'preserve', 'rules' => [['field' => 'email']]]);
        $outer = ProfileDefinition::fromArray('outer', ['mode' => 'preserve', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'nested']]]]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$nested, $outer]))
            ->anonymizeWithReport('outer', ['payload' => '{"email":"keep","name":"hide"}']);

        self::assertSame(['payload' => '{"email":"keep","name":"[REDACTED]"}'], $result->data());
        self::assertSame(['/payload/name'], $result->anonymizedPaths());
    }

    public function testEmbeddedCodecEncodeFailuresAreContextual(): void
    {
        $codec = new StaticPayloadCodec('failing', ['email' => 'secret'], 'email', 'embedded encode failed');
        $nested = ProfileDefinition::fromArray('nested', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $outer = ProfileDefinition::fromArray('outer', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'failing', 'profile' => 'nested']]]]);
        $this->expectExceptionMessageMatches('/failed to encode embedded payload at "\/payload"/');
        new ArrayAnonymizer(new InMemoryProfileProvider([$nested, $outer]), [], [$codec])->anonymize('outer', ['payload' => 'encoded']);
    }

    public function testEmbeddedDepthIsRestoredWhenNestedTraversalFails(): void
    {
        $nested = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']]]],
        ]);
        $outer = ProfileDefinition::fromArray('outer', [
            'mode' => 'redact',
            'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'nested']]],
        ]);
        $profiles = new ProfileRegistry([$nested, $outer]);
        $strategies = new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]);
        $codecs = new PayloadCodecRegistry([new JsonPayloadCodec()]);
        $traversal = new ArrayTraversal($profiles, $strategies, $codecs);
        $budget = new ExecutionBudget(new AnonymizationLimits(maxEmbeddedPayloadDepth: 1));

        try {
            $traversal->apply('outer', ['payload' => '{"customer":"invalid"}'], budget: $budget);
            self::fail('Expected the nested traversal to reject the invalid customer shape.');
        } catch (UnsupportedValue) {
            self::addToAssertionCount(1);
        }

        $result = $traversal->apply('outer', ['payload' => '{"customer":{"email":"secret"}}'], budget: $budget);
        self::assertSame(['payload' => '{"customer":{"email":"[REDACTED]"}}'], $result->data());
    }

    public function testSelectedChildrenAndEachHonorExactDepthBoundaries(): void
    {
        $children = ProfileDefinition::fromArray('children', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']]]],
        ]);
        $data = ['customer' => ['email' => 'secret']];

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$children]), limits: new AnonymizationLimits(maxDepth: 1))
                ->anonymize('children', $data);
            self::fail('Expected the child map to exceed the depth limit.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('/customer', $exception->getMessage());
        }

        self::assertSame(['customer' => ['email' => '[REDACTED]']], new ArrayAnonymizer(
            new InMemoryProfileProvider([$children]),
            limits: new AnonymizationLimits(maxDepth: 3),
        )->anonymize('children', $data));

        $each = ProfileDefinition::fromArray('each', [
            'mode' => 'redact',
            'rules' => [['field' => 'items', 'each' => [['field' => 'email']]]],
        ]);
        $items = ['items' => [['email' => 'one'], ['email' => 'two']]];

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$each]), limits: new AnonymizationLimits(maxDepth: 1))
                ->anonymize('each', $items);
            self::fail('Expected each item to exceed the depth limit.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('/items/0', $exception->getMessage());
        }

        self::assertSame(['items' => [['email' => '[REDACTED]'], ['email' => '[REDACTED]']]], new ArrayAnonymizer(
            new InMemoryProfileProvider([$each]),
            limits: new AnonymizationLimits(maxDepth: 3),
        )->anonymize('each', $items));
    }

    public function testTraversalBudgetsCoverSelectedVisitsCollectionsAndUnselectedMaps(): void
    {
        $profile = ProfileDefinition::fromArray('budgeted', [
            'mode' => 'redact',
            'rules' => [['field' => 'email'], ['field' => 'items', 'each' => [['field' => 'email']]]],
        ]);

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), limits: new AnonymizationLimits(maxVisitedNodes: 1))
                ->anonymize('budgeted', ['email' => 'secret']);
            self::fail('Expected the selected visit to exceed the node budget.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('/email', $exception->getMessage());
        }

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), limits: new AnonymizationLimits(maxCollectionEntries: 1))
                ->anonymize('budgeted', ['items' => [['email' => 'one'], ['email' => 'two']]]);
            self::fail('Expected the each collection to exceed the collection budget.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('/items', $exception->getMessage());
        }

        $unselected = ProfileDefinition::fromArray('unselected', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$unselected]), limits: new AnonymizationLimits(maxVisitedNodes: 1))
                ->anonymize('unselected', ['meta' => ['value' => 'secret']]);
            self::fail('Expected the unselected map to exceed the node budget.');
        } catch (ExecutionLimitExceeded $exception) {
            self::assertStringContainsString('/meta', $exception->getMessage());
        }
    }

    public function testDirectTraversalRetainsCallerBudgetAndRejectsInvalidDocumentShapes(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $profiles = new ProfileRegistry([$profile]);
        $strategies = new ReplacementStrategyRegistry([new DefaultRedactionStrategy(), new StarMaskStrategy()]);
        $codecs = new PayloadCodecRegistry([new JsonPayloadCodec()]);
        $traversal = new ArrayTraversal($profiles, $strategies, $codecs);
        $budget = new ExecutionBudget(new AnonymizationLimits(maxVisitedNodes: 3));

        $traversal->apply('customer', ['email' => 'one'], budget: $budget);

        try {
            $traversal->apply('customer', ['email' => 'two'], budget: $budget);
            self::fail('Expected the reused budget to be exhausted.');
        } catch (ExecutionLimitExceeded) {
            self::addToAssertionCount(1);
        }

        $list = ProfileDefinition::fromArray('list', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$list]));
        $this->expectException(InvalidArgumentException::class);
        $anonymizer->anonymizeDocument('list', 'json', '{"email":"secret"}');
    }

    public function testPreserveModeCanUseAnExplicitStrategy(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'email', 'strategy' => 'star_mask']],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['email' => 'abc', 'name' => 'Ada']);

        self::assertSame(['email' => '***', 'name' => '[REDACTED]'], $result->data());
        self::assertSame(['/email', '/name'], $result->anonymizedPaths());
    }

    public function testCustomStrategyAndCodecAreUsed(): void
    {
        $strategy = new StaticReplacementStrategy('suffix', suffix: '-masked');
        $codec = new StaticPayloadCodec('pipe', decodeKey: 'email');
        $profile = ProfileDefinition::fromArray('custom', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'suffix']]]);

        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy], [$codec]);

        self::assertSame('secret-masked', $anonymizer->anonymizePayload('custom', 'pipe', 'secret'));
    }

    public function testPayloadAndEmbeddedCodecFailuresAreContextual(): void
    {
        $profile = ProfileDefinition::fromArray('payload', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            $anonymizer->anonymizePayload('payload', 'json', '{');
            self::fail('Expected a payload codec failure.');
        } catch (PayloadCodecFailure $exception) {
            self::assertStringContainsString('profile "payload"', $exception->getMessage());
        }

        $this->expectExceptionMessageMatches('/embedded payload at "\/payload"/');
        $nested = ProfileDefinition::fromArray('nested', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $outer = ProfileDefinition::fromArray('outer', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'nested']]]]);
        new ArrayAnonymizer(new InMemoryProfileProvider([$nested, $outer]))->anonymize('outer', ['payload' => '{']);
    }

    public function testTopLevelPayloadHonorsInputExecutionLimit(): void
    {
        $profile = ProfileDefinition::fromArray('limited-payload', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            limits: new AnonymizationLimits(maxInputBytes: 1),
        );

        $this->expectException(ExecutionLimitExceeded::class);
        $anonymizer->anonymizePayload('limited-payload', 'json', '{"email":"secret"}');
    }

    public function testTopLevelPayloadCountsUtf8CharactersForInputLimits(): void
    {
        $profile = ProfileDefinition::fromArray('unicode-payload', ['mode' => 'redact', 'rules' => [['field' => 'payload']]]);
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([$profile]),
            codecs: [new StaticPayloadCodec('unicode')],
            limits: new AnonymizationLimits(maxInputBytes: 1),
        );

        self::assertSame('[REDACTED]', $anonymizer->anonymizePayload('unicode-payload', 'unicode', '€'));
    }

    public function testExpectedMapFailureNamesEmbeddedPayloads(): void
    {
        $exception = PayloadCodecFailure::expectedMap('outer', 'json', '/payload');

        self::assertStringContainsString('embedded payload at "/payload"', $exception->getMessage());
    }

    public function testTopLevelPayloadRejectsUnknownCodecNonMapAndEncodeFailures(): void
    {
        $profile = ProfileDefinition::fromArray('payload', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            $anonymizer->anonymizePayload('payload', 'missing', '{}');
            self::fail('Expected an unknown codec failure.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $listCodec = new StaticPayloadCodec('list', [0 => 'not-a-map']);
        $listAnonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [], [$listCodec]);

        try {
            $listAnonymizer->anonymizePayload('payload', 'list', '{}');
            self::fail('Expected a non-map failure.');
        } catch (PayloadCodecFailure $exception) {
            self::assertStringContainsString('non-map payload', $exception->getMessage());
            $this->addToAssertionCount(1);
        }

        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [], [new StaticPayloadCodec('plain')])->anonymizeDocument('payload', 'plain', '{}');
            self::fail('Expected a non-document codec failure.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('does not support document roots', $exception->getMessage());
        }

        $failingCodec = new StaticPayloadCodec('failing', ['email' => 'secret'], 'email', 'encode failed');
        $this->expectExceptionMessageMatches('/failed to encode payload/');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [], [$failingCodec])->anonymizePayload('payload', 'failing', '{}');
    }

    public function testEmbeddedPayloadRejectsNonStringAndMissingCodecAtTraversalBoundary(): void
    {
        $nested = ProfileDefinition::fromArray('nested', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $outer = ProfileDefinition::fromArray('outer', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'nested']]]]);
        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$nested, $outer]))->anonymize('outer', ['payload' => 42]);
    }

    public function testTraversalHelpersReportUnavailableExtensions(): void
    {
        $profiles = new ProfileRegistry([]);
        $codecs = new PayloadCodecRegistry([]);
        $strategies = new ReplacementStrategyRegistry([]);
        $traversal = new ArrayTraversal($profiles, $strategies, $codecs);
        $embedded = new EmbeddedPayloadTraversal($codecs, $traversal);
        $value = '{}';
        $report = new AnonymizationReport();

        try {
            $embedded->apply('outer', $value, '/payload', 'missing', 'nested', $report);
            self::fail('Expected missing codec failure.');
        } catch (PayloadCodecFailure) {
            $this->addToAssertionCount(1);
        }

        $rule = ProfileDefinition::fromArray('custom', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'missing']]])->rules()[0];
        $redactor = new RedactTraversalPolicy($strategies);
        $data = ['email' => 'secret'];
        $this->expectException(UnsupportedValue::class);
        $redactor->selected('custom', $data['email'], $rule, JsonPointer::root()->append('email'), $report);
    }

    public function testPreserveStringHelperReportsUnavailableStrategy(): void
    {
        $rule = ProfileDefinition::fromArray('custom', ['mode' => 'preserve', 'rules' => [['field' => 'email', 'strategy' => 'missing']]])->rules()[0];
        $helper = new PreserveStringSelection(new ReplacementStrategyRegistry([]));
        $value = 'secret';
        $paths = [];
        $this->expectException(UnsupportedValue::class);
        $helper->apply($value, $rule, '/email', 'custom', $paths);
    }

    public function testPreserveStringHelperRejectsTraversalWithoutFallback(): void
    {
        $rule = ProfileDefinition::fromArray('custom', ['mode' => 'preserve', 'rules' => [['field' => 'address', 'children' => [['field' => 'street']]]]])->rules()[0];
        $helper = new PreserveStringSelection(new ReplacementStrategyRegistry([]));
        $value = 'secret';
        $paths = [];

        $this->expectException(UnsupportedValue::class);
        $helper->apply($value, $rule, '/address', 'custom', $paths);
    }

    public function testPreserveStringHelperAppliesAnExplicitStrategy(): void
    {
        $rule = ProfileDefinition::fromArray('custom', ['mode' => 'preserve', 'rules' => [['field' => 'email', 'strategy' => 'star_mask']]])->rules()[0];
        $helper = new PreserveStringSelection(new ReplacementStrategyRegistry([new StarMaskStrategy()]));
        $value = 'secret';
        $paths = [];

        $helper->apply($value, $rule, '/email', 'custom', $paths);

        self::assertSame('******', $value);
        self::assertSame(['/email'], $paths);
    }

    public function testPreserveStringHelperHandlesPreserveScalarFallback(): void
    {
        $rule = ProfileDefinition::fromArray('custom', ['mode' => 'preserve', 'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'preserve']]])->rules()[0];
        $helper = new PreserveStringSelection(new ReplacementStrategyRegistry([]));
        $value = 'secret';
        $paths = [];

        $this->expectException(UnsupportedValue::class);
        $helper->apply($value, $rule, '/address', 'custom', $paths);
    }

    public function testPreservePolicyRejectsNonStringScalarFallback(): void
    {
        $policy = new PreserveTraversalPolicy(new ReplacementStrategyRegistry([]));
        $value = 42;
        $report = new AnonymizationReport();

        $this->expectException(UnsupportedValue::class);
        $policy->scalar('custom', $value, ScalarAction::REDACT, JsonPointer::root()->append('address'), $report);
    }

    public function testUnknownEmbeddedProfileAndCyclesFailDuringConstruction(): void
    {
        $unknown = ProfileDefinition::fromArray('outer', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'missing']]]]);
        $this->expectExceptionMessageMatches('/unknown embedded profile/');
        new ArrayAnonymizer(new InMemoryProfileProvider([$unknown]));
    }

    public function testEmbeddedProfileCyclesFailDuringConstruction(): void
    {
        $first = ProfileDefinition::fromArray('first', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'second']]]]);
        $second = ProfileDefinition::fromArray('second', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'first']]]]);

        $this->expectExceptionMessageMatches('/reference cycle detected/');
        new ArrayAnonymizer(new InMemoryProfileProvider([$first, $second]));
    }

    public function testObjectInputsAreRejectedByTheArrayEngine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->anonymizer->anonymize('custom', new stdClass());
    }

    public function testObjectInputsAreRejectedByTheArrayReportEngine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->anonymizer->anonymizeWithReport('custom', new stdClass());
    }

    public function testArrayEngineObjectPortsRejectRequests(): void
    {
        $request = new ObjectAnonymizationRequest('custom', new stdClass());

        $this->expectException(ObjectAnonymizationUnavailable::class);
        $this->anonymizer->anonymizeObject($request);
    }

    public function testArrayEngineObjectReportPortRejectsRequests(): void
    {
        $request = new ObjectAnonymizationRequest('custom', new stdClass());

        $this->expectException(ObjectAnonymizationUnavailable::class);
        $this->anonymizer->anonymizeObjectWithReport($request);
    }
}
