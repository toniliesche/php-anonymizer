<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Extension\Transformation;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleTreeTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileCollection;
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
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticValueTransformation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PartialMaskTransformation::class)]
#[CoversClass(NumericBucketTransformation::class)]
#[CoversClass(NullifyTransformation::class)]
#[CoversClass(DateGeneralizeTransformation::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(AnonymizerBuilder::class)]
#[UsesClass(ArrayAnonymizer::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactTraversalPolicy::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(ArrayTraversal::class)]
#[UsesClass(ExecutionBudget::class)]
#[UsesClass(RuleTreeTraversal::class)]
#[UsesClass(EmbeddedPayloadTraversal::class)]
#[UsesClass(JsonPayloadCodec::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileCollection::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(ProfileReferenceValidator::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(CoreTransformationFactory::class)]
#[UsesClass(TransformationDefinition::class)]
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
final class CoreTransformationTest extends TestCase
{
    public function testCoreTransformationFactoryExposesAllBuiltins(): void
    {
        $defaults = CoreTransformationFactory::defaults();
        self::assertCount(4, $defaults);
        self::assertSame(['nullify', 'partial_mask', 'date_generalize', 'numeric_bucket'], array_map(static fn (object $transformation): string => $transformation->id(), $defaults));
    }

    public function testCoreTransformations(): void
    {
        $anonymizer = AnonymizerBuilder::create()->withProfile(ProfileDefinition::fromArray('p', ['mode' => 'redact', 'rules' => [
            ['field' => 'name', 'transform' => ['type' => 'partial_mask', 'options' => ['suffix' => 2]]],
            ['field' => 'age', 'transform' => ['type' => 'numeric_bucket', 'options' => ['size' => 10]]],
            ['field' => 'secret', 'transform' => ['type' => 'nullify', 'options' => []]],
            ['field' => 'when', 'transform' => ['type' => 'date_generalize', 'options' => ['unit' => 'month']]],
        ]]))->build();
        $result = $anonymizer->anonymize('p', ['name' => 'abcdef', 'age' => 27, 'secret' => 'x', 'when' => '2025-04-17T12:30:00+02:00']);
        self::assertSame(['name' => '****ef', 'age' => 20, 'secret' => null, 'when' => '2025-04-01T00:00:00.000+02:00'], $result);
    }

    public function testCustomTransformationIsUsedInBothTraversalModes(): void
    {
        $transformation = new StaticValueTransformation('custom', prefix: 'custom:');
        $anonymizer = AnonymizerBuilder::create()
            ->withProfiles([
                ProfileDefinition::fromArray('redact', ['mode' => 'redact', 'rules' => [['field' => 'value', 'transform' => ['type' => 'custom', 'options' => []]]]]),
                ProfileDefinition::fromArray('preserve', ['mode' => 'preserve', 'rules' => [['field' => 'value', 'transform' => ['type' => 'custom', 'options' => []]]]]),
            ])
            ->withValueTransformation($transformation)
            ->build();

        self::assertSame(['value' => 'custom:secret'], $anonymizer->anonymize('redact', ['value' => 'secret']));
        self::assertSame(['value' => 'custom:secret'], $anonymizer->anonymize('preserve', ['value' => 'secret']));
    }
}
