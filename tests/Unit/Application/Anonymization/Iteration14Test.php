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
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactUnselectedValues;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleTreeTraversal;
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
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(RedactUnselectedValues::class)]
#[CoversClass(RuleTreeTraversal::class)]
#[CoversClass(ProfileDefinition::class)]
#[CoversClass(KeyNormalizer::class)]
#[CoversClass(ConditionDefinition::class)]
#[UsesClass(InvalidProfileDefinition::class)]
#[CoversClass(RuleDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(AnonymizationContext::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactTraversalPolicy::class)]
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
final class Iteration14Test extends TestCase
{
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
