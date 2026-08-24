<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

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
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
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
use PhpAnonymizer\Anonymizer\Application\Anonymization\ValueTransformationContext;
use PhpAnonymizer\Anonymizer\Application\Exception\ContextRequired;
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
use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticContextualValueTransformation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(RedactUnselectedValues::class)]
#[CoversClass(PreserveTraversalPolicy::class)]
#[CoversClass(RedactTraversalPolicy::class)]
#[CoversClass(RuleTreeTraversal::class)]
#[UsesClass(CompiledProfile::class)]
#[UsesClass(CompiledRuleList::class)]
#[UsesClass(RuleSelector::class)]
#[UsesClass(SelectedRule::class)]
#[CoversClass(ProfileDefinition::class)]
#[CoversClass(KeyNormalizer::class)]
#[CoversClass(ConditionDefinition::class)]
#[UsesClass(InvalidProfileDefinition::class)]
#[UsesClass(ContextRequired::class)]
#[CoversClass(ValueTransformationContext::class)]
#[CoversClass(RuleDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(AnonymizationContext::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(ArrayTraversal::class)]
#[UsesClass(ExecutionBudget::class)]
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
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(InMemoryProfileProvider::class)]
#[UsesClass(RuntimeAssembler::class)]
#[UsesClass(SelectedValueDispatcher::class)]
#[UsesClass(TraversalExecutionContext::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(AnonymizerRuntime::class)]
#[UsesClass(NdjsonAnonymizationService::class)]
#[UsesClass(PayloadAnonymizationService::class)]
#[UsesClass(TransformationDefinition::class)]
#[UsesClass(DateGeneralizeTransformation::class)]
#[UsesClass(NullifyTransformation::class)]
#[UsesClass(NumericBucketTransformation::class)]
#[UsesClass(PartialMaskTransformation::class)]
final class TraversalPolicyTest extends TestCase
{
    public function testSelectedTransformationsRunInPreserveAndRedactModes(): void
    {
        $preserve = ProfileDefinition::fromArray('preserve-transform', [
            'mode' => 'preserve',
            'rules' => [['field' => 'date', 'transform' => ['type' => 'date_generalize', 'options' => ['unit' => 'day']]]],
        ]);
        $redact = ProfileDefinition::fromArray('redact-transform', [
            'mode' => 'redact',
            'rules' => [['field' => 'date', 'transform' => ['type' => 'date_generalize', 'options' => ['unit' => 'day']]]],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$preserve, $redact]));

        self::assertSame(['date' => '2025-04-17T00:00:00.000+00:00'], $anonymizer->anonymize('preserve-transform', ['date' => '2025-04-17T12:30:00+00:00']));
        self::assertSame(['date' => '2025-04-17T00:00:00.000+00:00'], $anonymizer->anonymize('redact-transform', ['date' => '2025-04-17T12:30:00+00:00']));
    }

    public function testContextualTransformationsRequireAndConsumeContext(): void
    {
        $definition = ['transform' => ['type' => 'contextual_transform'], 'key_id' => 'tenant'];
        $profiles = [
            ProfileDefinition::fromArray('preserve-contextual', ['mode' => 'preserve', 'rules' => [['field' => 'email'] + $definition]]),
            ProfileDefinition::fromArray('redact-contextual', ['mode' => 'redact', 'rules' => [['field' => 'email'] + $definition]]),
        ];
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider($profiles), transformations: [new StaticContextualValueTransformation()]);

        try {
            $anonymizer->anonymize('preserve-contextual', ['email' => 'secret']);
            self::fail('Expected preserve contextual transformation to require context.');
        } catch (ContextRequired) {
            self::addToAssertionCount(1);
        }

        try {
            $anonymizer->anonymize('redact-contextual', ['email' => 'secret']);
            self::fail('Expected redact contextual transformation to require context.');
        } catch (ContextRequired) {
            self::addToAssertionCount(1);
        }
        self::assertSame(['email' => 'secret:preserve-contextual:/email'], $anonymizer->anonymize('preserve-contextual', ['email' => 'secret'], new AnonymizationContext(['tenant' => 'acme'])));
        self::assertSame(['email' => 'secret:redact-contextual:/email'], $anonymizer->anonymize('redact-contextual', ['email' => 'secret'], new AnonymizationContext(['tenant' => 'acme'])));
    }

    public function testPoliciesRejectUnavailableTransformations(): void
    {
        $rule = RuleDefinition::fromArray(['field' => 'value', 'transform' => ['type' => 'missing', 'options' => []]]);
        $report = AnonymizationReport::collecting();
        $path = JsonPointer::fromString('/value');
        $preserve = new PreserveTraversalPolicy(new ReplacementStrategyRegistry([]), new TransformationRegistry([]));
        $redact = new RedactTraversalPolicy(new ReplacementStrategyRegistry([]), new TransformationRegistry([]));
        $value = 'secret';

        try {
            $preserve->selected('profile', $value, $rule, $path, $report);
            self::fail('Preserve policy should reject missing transformations.');
        } catch (UnsupportedValue) {
            self::addToAssertionCount(1);
        }
        $this->expectException(UnsupportedValue::class);
        $redact->selected('profile', $value, $rule, $path, $report);
    }

    public function testNormalizedKeysMatchWithoutRenamingOutputOrReportPaths(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'key_normalization' => true,
            'rules' => [['field' => 'email_address']],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $result = $anonymizer->anonymizeWithReport('customer', ['emailAddress' => 'secret']);

        self::assertSame(['emailAddress' => '[REDACTED]'], $result->data());
        self::assertSame(['/emailAddress'], $result->anonymizedPaths());
    }

    public function testKeyNormalizerTrimsAndSplitsAsciiBoundaries(): void
    {
        self::assertSame('http response 2', KeyNormalizer::normalize(' HTTPResponse2 '));
    }

    public function testKeyNormalizationDefaultsToDisabled(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);

        self::assertFalse($profile->keyNormalization());
    }

    public function testExactSpellingWinsNormalizedCollision(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'key_normalization' => true,
            'rules' => [
                ['field' => 'email_address', 'strategy' => 'star_mask'],
                ['field' => 'emailAddress'],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        self::assertSame(['emailAddress' => '[REDACTED]'], $anonymizer->anonymize('customer', ['emailAddress' => 'secret']));
        self::assertSame(['email_address' => '******'], $anonymizer->anonymize('customer', ['email_address' => 'secret']));
    }

    public function testGlobalRulesApplyAtNestedMapDepthsAndInsideLists(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'key_normalization' => true,
            'global_rules' => [['field' => 'email']],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        self::assertSame([
            'email' => '[REDACTED]',
            'nested' => ['email' => '[REDACTED]'],
            'items' => [['email' => '[REDACTED]']],
        ], $anonymizer->anonymize('customer', [
            'email' => 'one',
            'nested' => ['email' => 'two'],
            'items' => [['email' => 'three']],
        ]));
    }

    public function testGlobalEachUsesPreserveAndRedactSemantics(): void
    {
        $redact = ProfileDefinition::fromArray('redact', [
            'mode' => 'redact',
            'global_rules' => [['field' => 'tags', 'each' => true]],
        ]);
        $preserve = ProfileDefinition::fromArray('preserve', [
            'mode' => 'preserve',
            'global_rules' => [['field' => 'tags', 'each' => true]],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$redact, $preserve]));

        self::assertSame(['tags' => ['[REDACTED]', '[REDACTED]']], $anonymizer->anonymize('redact', ['tags' => ['one', 'two']]));
        self::assertSame(['tags' => ['one', 'two'], 'other' => '[REDACTED]'], $anonymizer->anonymize('preserve', ['tags' => ['one', 'two'], 'other' => 'secret']));
    }

    public function testNormalizedConditionLookupUsesEquivalentInputKey(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'key_normalization' => true,
            'rules' => [['field' => 'email', 'when' => ['path' => 'account_type', 'equals' => 'person']]],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        self::assertSame(['accountType' => 'person', 'email' => '[REDACTED]'], $anonymizer->anonymize('customer', ['accountType' => 'person', 'email' => 'secret']));
        self::assertSame(['email' => 'secret'], $anonymizer->anonymize('customer', ['email' => 'secret']));
    }

    public function testConditionalGlobalRuleAppliesWhenLocalConditionDoesNotMatch(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email', 'when' => ['path' => 'kind', 'equals' => 'person']]],
            'global_rules' => [['field' => 'email', 'when' => ['path' => 'kind', 'equals' => 'company']]],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        self::assertSame(['kind' => 'company', 'email' => '[REDACTED]'], $anonymizer->anonymize('customer', ['kind' => 'company', 'email' => 'secret']));
        self::assertSame(['kind' => 'other', 'email' => 'secret'], $anonymizer->anonymize('customer', ['kind' => 'other', 'email' => 'secret']));
    }

    public function testNormalizedConditionUsesFirstEquivalentInputKey(): void
    {
        $condition = ConditionDefinition::fromArray(['path' => 'account_type', 'equals' => 'person']);

        self::assertFalse($condition->matches([
            'accountType' => 'company',
            'account-type' => 'person',
        ], true));
    }

    public function testRejectsGlobalStructuralRulesAndProfilesWithoutSelectors(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', [
            'mode' => 'redact',
            'global_rules' => [['field' => 'address', 'children' => [['field' => 'email']]]],
        ]);
    }

    public function testRejectsGlobalEmbeddedRule(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', [
            'mode' => 'redact',
            'global_rules' => [['field' => 'address', 'embedded' => ['codec' => 'json', 'profile' => 'nested']]],
        ]);
    }

    public function testRejectsGlobalOnScalarRule(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', [
            'mode' => 'redact',
            'global_rules' => [['field' => 'address', 'on_scalar' => 'redact']],
        ]);
    }

    public function testRejectsNonListGlobalRulesAndDuplicateGlobalFields(): void
    {
        try {
            ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'global_rules' => ['email']]);
            self::fail('Expected invalid global rule list.');
        } catch (InvalidProfileDefinition $exception) {
            self::assertStringContainsString('global_rules', $exception->getMessage());
        }

        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', [
            'mode' => 'redact',
            'global_rules' => [['field' => 'email'], ['field' => 'email']],
        ]);
    }

    public function testRejectsNestedGlobalEachRules(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', [
            'mode' => 'redact',
            'global_rules' => [['field' => 'items', 'each' => [['field' => 'email']]]],
        ]);
    }

    public function testRejectsProfileWithoutRulesOrGlobalRules(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', ['mode' => 'redact']);
    }

    public function testRejectsEmptyGlobalRulesAndNonBooleanNormalization(): void
    {
        try {
            ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'global_rules' => []]);
            self::fail('Expected empty global rules to be rejected.');
        } catch (InvalidProfileDefinition $exception) {
            self::assertStringContainsString('global_rules', $exception->getMessage());
        }

        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', [
            'mode' => 'redact',
            'key_normalization' => 'yes',
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRedactUnselectedValuesEscapesNestedMapKeys(): void
    {
        $value = ['nested' => ['a/b' => 'secret']];
        $paths = [];
        new RedactUnselectedValues()->apply($value, '', $paths);

        self::assertSame(['nested' => ['a/b' => '[REDACTED]']], $value);
        self::assertSame(['/nested/a~1b'], $paths);
    }
}
