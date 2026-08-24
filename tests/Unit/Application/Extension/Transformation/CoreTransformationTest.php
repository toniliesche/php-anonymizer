<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Extension\Transformation;

use DateTimeInterface;
use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
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
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticValueTransformation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;
use stdClass;

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
#[UsesClass(CompiledProfile::class)]
#[UsesClass(CompiledRuleList::class)]
#[UsesClass(RuleSelector::class)]
#[UsesClass(SelectedRule::class)]
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
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(AnonymizerFacade::class)]
#[UsesClass(AnonymizerRuntime::class)]
#[UsesClass(NdjsonAnonymizationService::class)]
#[UsesClass(PayloadAnonymizationService::class)]
#[UsesClass(RuntimeAssembler::class)]
#[UsesClass(SelectedValueDispatcher::class)]
#[UsesClass(TraversalExecutionContext::class)]
final class CoreTransformationTest extends TestCase
{
    public function testBuiltInTransformationContractsAndValidation(): void
    {
        $date = new DateGeneralizeTransformation();
        self::assertSame('date_generalize', $date->id());
        self::assertSame(['temporal', 'string'], $date->supportedTypes());
        self::assertSame('same', $date->outputType());
        $generalized = $date->transform(new DateTimeImmutable('2025-04-17T12:30:00+00:00'), ['unit' => 'month']);
        self::assertInstanceOf(DateTimeInterface::class, $generalized);
        self::assertSame('2025-04-01T00:00:00.000+00:00', $generalized->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('2025-01-01T00:00:00.000+00:00', $date->transform('2025-04-17T12:30:00+00:00', ['unit' => 'year']));
        self::assertSame('2025-04-17T12:00:00.000+00:00', $date->transform('2025-04-17T12:30:00+00:00', ['unit' => 'hour']));
        self::assertSame('2025-04-17T00:00:00.000+00:00', $date->transform('2025-04-17T12:30:00+00:00', ['unit' => 'day']));

        try {
            $date->validateOptions([]);
            self::fail('Expected date generalization options to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
        $this->expectException(InvalidArgumentException::class);
        $date->validateOptions(['unit' => 'minute']);
    }

    public function testBuiltInTransformationsRejectInvalidValuesAndOptions(): void
    {
        $nullify = new NullifyTransformation();
        self::assertSame('nullify', $nullify->id());
        self::assertSame(['scalar', 'temporal'], $nullify->supportedTypes());
        self::assertSame('null', $nullify->outputType());
        self::assertNull($nullify->transform('value', []));

        try {
            $nullify->validateOptions(['unexpected' => true]);
            self::fail('Expected nullify options to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        $bucket = new NumericBucketTransformation();
        self::assertSame('numeric_bucket', $bucket->id());
        self::assertSame(['int', 'float'], $bucket->supportedTypes());
        self::assertSame('same', $bucket->outputType());
        self::assertSame(20, $bucket->transform(27, ['size' => 10]));

        try {
            $bucket->validateOptions(['size' => 0]);
            self::fail('Expected numeric bucket options to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        try {
            $bucket->validateOptions(['size' => 10, 'origin' => 'invalid']);
            self::fail('Expected numeric bucket origin to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
        self::assertSame(5.0, $bucket->transform(7.5, ['size' => 5.0, 'origin' => 0.0]));

        try {
            $bucket->transform(5, ['size' => 'invalid']);
            self::fail('Expected invalid numeric bucket options to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
        $this->expectException(InvalidArgumentException::class);
        $bucket->transform('27', ['size' => 10]);
    }

    public function testPartialMaskAndDateGeneralizeRejectInvalidInput(): void
    {
        $mask = new PartialMaskTransformation();
        self::assertSame('partial_mask', $mask->id());
        self::assertSame(['string'], $mask->supportedTypes());
        self::assertSame('string', $mask->outputType());
        self::assertSame('ab****yz', $mask->transform('abcdefyz', ['prefix' => 2, 'suffix' => 2]));
        self::assertSame('abc', $mask->transform('abc', ['prefix' => 2, 'suffix' => 2]));
        self::assertSame('ab####', $mask->transform('abcdef', ['prefix' => 2, 'suffix' => 0, 'character' => '#']));

        try {
            $mask->validateOptions(['prefix' => -1]);
            self::fail('Expected partial mask options to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        try {
            $mask->validateOptions(['character' => '']);
            self::fail('Expected empty mask character to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        try {
            $mask->transform(42, []);
            self::fail('Expected non-string mask input to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        $date = new DateGeneralizeTransformation();

        try {
            $date->transform(new stdClass(), ['unit' => 'day']);
            self::fail('Expected unsupported date input to be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
        $this->expectException(InvalidArgumentException::class);
        $date->transform('not-a-date', ['unit' => 'day']);
    }

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
