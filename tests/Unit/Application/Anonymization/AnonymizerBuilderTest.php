<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use LogicException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilderFactory;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFacade;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\NdjsonAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ObjectAnonymizationRequest;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\PayloadAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
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
use PhpAnonymizer\Anonymizer\Application\Exception\ObjectAnonymizationUnavailable;
use PhpAnonymizer\Anonymizer\Application\Extension\ObjectAnonymizerInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileCollection;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\EmbeddedProfileReferenceGraph;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\CoreTransformationFactory;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\DateGeneralizeTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\NullifyTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\NumericBucketTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\PartialMaskTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\ConditionalDocumentPayloadCodec;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\ProfileProviderSequence;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticAnonymizerExtension;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticPayloadCodec;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticProfileProvider;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticReplacementStrategy;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticValueTransformation;
use PhpAnonymizer\Anonymizer\Tests\Support\MismatchedObject;
use PhpAnonymizer\AnonymizerSymfonySerializer\Application\Anonymization\Result\ObjectAnonymizationResult;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(AnonymizerBuilder::class)]
#[CoversClass(AnonymizerFacade::class)]
#[CoversClass(AnonymizerBuilderFactory::class)]
#[CoversClass(ObjectAnonymizationResult::class)]
#[UsesClass(ObjectAnonymizationRequest::class)]
#[CoversClass(ObjectAnonymizationUnavailable::class)]
#[UsesClass(NdjsonAnonymizationService::class)]
#[UsesClass(PayloadAnonymizationService::class)]
#[UsesClass(RuntimeAssembler::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(AnonymizationContext::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
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
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(ProfileReferenceValidator::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(CoreTransformationFactory::class)]
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(SelectedValueDispatcher::class)]
#[UsesClass(TraversalExecutionContext::class)]
#[UsesClass(ProfileCollection::class)]
#[UsesClass(DateGeneralizeTransformation::class)]
#[UsesClass(NullifyTransformation::class)]
#[UsesClass(NumericBucketTransformation::class)]
#[UsesClass(PartialMaskTransformation::class)]
#[UsesClass(AuditAnonymizationResult::class)]
#[UsesClass(AuditEntry::class)]
#[UsesClass(DocumentAnonymizationResult::class)]
#[UsesClass(DocumentPayloadAnonymizationResult::class)]
#[UsesClass(NdjsonAnonymizationResult::class)]
#[UsesClass(PayloadAnonymizationResult::class)]
#[AllowMockObjectsWithoutExpectations]
final class AnonymizerBuilderTest extends TestCase
{
    public function testFactoryCreatesAConfiguredBuilder(): void
    {
        $builder = AnonymizerBuilderFactory::create(new StaticProfileProvider([]), [], [], new AnonymizationLimits(), [], null, []);

        $builder->withLimits(new AnonymizationLimits());
        self::addToAssertionCount(1);

        $extension = new StaticAnonymizerExtension(ProfileDefinition::fromArray('factory.extension', ['mode' => 'redact', 'rules' => [['field' => 'email']]]));
        $objectAnonymizer = self::createStub(ObjectAnonymizerInterface::class);
        AnonymizerBuilderFactory::create(
            new StaticProfileProvider([]),
            [],
            [],
            new AnonymizationLimits(),
            [],
            $objectAnonymizer,
            [$extension],
        );
        self::addToAssertionCount(1);
    }

    public function testEachFluentRegistrationReturnsAnIndependentBuilder(): void
    {
        $profile = ProfileDefinition::fromArray('copy', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new StaticProfileProvider([ProfileDefinition::fromArray('copy.provider', ['mode' => 'redact', 'rules' => [['field' => 'email']]])]);
        $strategy = self::createStub(ReplacementStrategyInterface::class);
        $codec = self::createStub(PayloadCodecInterface::class);
        $transformation = self::createStub(ValueTransformationInterface::class);

        $builder = AnonymizerBuilder::create();
        self::assertNotSame($builder, $builder->withProfile($profile));
        self::assertNotSame($builder, $builder->withProfiles([$profile]));
        self::assertNotSame($builder, $builder->withProfileProvider($provider));
        self::assertNotSame($builder, $builder->withProfileProviders([$provider]));
        self::assertNotSame($builder, $builder->withReplacementStrategy($strategy));
        self::assertNotSame($builder, $builder->withReplacementStrategies([]));
        self::assertNotSame($builder, $builder->withPayloadCodec($codec));
        self::assertNotSame($builder, $builder->withPayloadCodecs([]));
        self::assertNotSame($builder, $builder->withValueTransformation($transformation));
        self::assertNotSame($builder, $builder->withValueTransformations([]));
        self::assertNotSame($builder, $builder->withLimits(new AnonymizationLimits()));
    }

    public function testBuiltFacadeDelegatesNdjsonForListProfiles(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'root' => 'list',
            'rules' => [['field' => 'email']],
        ]);
        $facade = AnonymizerBuilder::create()->withProfile($profile)->build();

        $facade->anonymizeNdjson('records', ['{"email":"secret"}']);
        self::addToAssertionCount(1);
    }

    public function testFacadeRejectsInvalidObjectAnonymizerResults(): void
    {
        $wrongData = self::createStub(ObjectAnonymizerInterface::class);
        $wrongData->method('anonymize')->willReturn(new AnonymizationResult(['not' => 'an object'], [], []));
        $facade = AnonymizerBuilder::create()->withObjectAnonymizer($wrongData)->build();

        $this->expectException(LogicException::class);
        $facade->anonymizeObject(new ObjectAnonymizationRequest('customer', new stdClass()));
    }

    public function testFacadeRejectsObjectAnonymizerResultOfWrongClass(): void
    {
        $wrongClass = self::createStub(ObjectAnonymizerInterface::class);
        $wrongClass->method('anonymize')->willReturn(new ObjectAnonymizationResult(new MismatchedObject(), new AnonymizationResult([], [], [])));
        $facade = AnonymizerBuilder::create()->withObjectAnonymizer($wrongClass)->build();

        $this->expectException(LogicException::class);
        $facade->anonymizeObject(new ObjectAnonymizationRequest('customer', new stdClass()));
    }

    public function testItBuildsAnImmutableFacadeWithAllRegistrationPorts(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $profileTwo = ProfileDefinition::fromArray('customer.two', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $providerProfile = ProfileDefinition::fromArray('provider.profile', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $extensionProfile = ProfileDefinition::fromArray('extension.profile', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new StaticProfileProvider([$providerProfile]);
        $strategy = new StaticReplacementStrategy('builder.strategy', suffix: '-safe');
        $codec = new StaticPayloadCodec('builder.codec');
        $transformation = new StaticValueTransformation('builder.transformation');
        $adapter = self::createStub(ObjectAnonymizerInterface::class);
        $adapter->method('anonymize')->willReturn(new ObjectAnonymizationResult(new stdClass(), new AnonymizationResult([], [], [])));
        $extension = new StaticAnonymizerExtension($extensionProfile);

        $original = new AnonymizerBuilder();
        $builder = $original
            ->withProfile($profile)
            ->withProfiles([$profileTwo])
            ->withProfileProvider($provider)
            ->withProfileProviders([new StaticProfileProvider([ProfileDefinition::fromArray('provider.two', ['mode' => 'redact', 'rules' => [['field' => 'email']]])])])
            ->withReplacementStrategy($strategy)
            ->withReplacementStrategies([])
            ->withPayloadCodec($codec)
            ->withPayloadCodec(new ConditionalDocumentPayloadCodec())
            ->withPayloadCodecs([])
            ->withValueTransformation($transformation)
            ->withValueTransformations([])
            ->withLimits(new AnonymizationLimits(maxDepth: 8))
            ->withObjectAnonymizer($adapter)
            ->withExtension($extension);

        self::assertNotSame($original, $builder);
        $facade = $builder->build();
        self::assertInstanceOf(AnonymizerFacade::class, $facade);
        $request = new ObjectAnonymizationRequest('customer', new stdClass());
        self::assertInstanceOf(stdClass::class, $facade->anonymizeObject($request));
        self::assertInstanceOf(ObjectAnonymizationResult::class, $facade->anonymizeObjectWithReport($request));
        $facade->anonymize('customer', new stdClass());
        $facade->anonymizeWithReport('customer', new stdClass());
        self::assertIsArray($facade->anonymize('customer', ['email' => 'secret']));
        self::assertInstanceOf(AnonymizationResult::class, $facade->anonymizeWithReport('customer', ['email' => 'secret']));
        $facade->anonymizeRecords('customer', [['email' => 'secret']]);
        $facade->anonymizeWithAudit('customer', ['email' => 'secret']);
        $facade->anonymizePayload('customer', 'builder.codec', 'payload');
        $facade->anonymizePayloadWithReport('customer', 'builder.codec', 'payload');
        $facade->anonymizeDocument('customer', 'shape', 'map');
        $facade->anonymizeDocumentWithReport('customer', 'shape', 'map');
        self::addToAssertionCount(6);
    }

    public function testItMaterializesGeneratorsAndRejectsInvalidProviders(): void
    {
        $provider = new StaticProfileProvider([ProfileDefinition::fromArray('generated', ['mode' => 'redact', 'rules' => [['field' => 'email']]])]);
        $builder = AnonymizerBuilder::create()->withProfileProviders(new ProfileProviderSequence([$provider]));

        self::assertSame(['email' => '[REDACTED]'], $builder->build()->anonymize('generated', ['email' => 'secret']));

        $this->expectException(InvalidArgumentException::class);
        AnonymizerBuilder::create()->withProfileProviders([new stdClass()]);
    }

    public function testDirectAndBuilderRuntimesExposeEquivalentOperations(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $direct = new ArrayAnonymizer(new StaticProfileProvider([$profile]));
        $built = AnonymizerBuilder::create()->withProfile($profile)->build();

        self::assertSame($direct->anonymize('customer', ['email' => 'secret']), $built->anonymize('customer', ['email' => 'secret']));
        self::assertSame($direct->anonymizeWithReport('customer', ['email' => 'secret'])->data(), $built->anonymizeWithReport('customer', ['email' => 'secret'])->data());
        self::assertSame($direct->anonymizePayload('customer', 'json', '{"email":"secret"}'), $built->anonymizePayload('customer', 'json', '{"email":"secret"}'));
        self::assertSame($direct->anonymizeDocument('customer', 'json', '{"email":"secret"}'), $built->anonymizeDocument('customer', 'json', '{"email":"secret"}'));
        self::assertSame(iterator_to_array($direct->anonymizeRecords('customer', [['email' => 'secret']]), false), iterator_to_array($built->anonymizeRecords('customer', [['email' => 'secret']]), false));
        $directAudit = $direct->anonymizeWithAudit('customer', ['email' => 'secret'])->entries();
        $builtAudit = $built->anonymizeWithAudit('customer', ['email' => 'secret'])->entries();
        self::assertCount(count($directAudit), $builtAudit);
        foreach ($directAudit as $index => $entry) {
            self::assertSame($entry->profile(), $builtAudit[$index]->profile());
            self::assertSame($entry->fingerprint(), $builtAudit[$index]->fingerprint());
            self::assertSame($entry->path(), $builtAudit[$index]->path());
            self::assertSame($entry->action(), $builtAudit[$index]->action());
            self::assertSame($entry->handler(), $builtAudit[$index]->handler());
            self::assertSame($entry->count(), $builtAudit[$index]->count());
        }
    }

    public function testItRejectsMultipleObjectAdapters(): void
    {
        $adapter = self::createStub(ObjectAnonymizerInterface::class);
        $this->expectException(InvalidArgumentException::class);
        AnonymizerBuilder::create()->withObjectAnonymizer($adapter)->withObjectAnonymizer($adapter);
    }

    public function testItReportsMissingObjectAdapter(): void
    {
        $object = new stdClass();
        $request = new ObjectAnonymizationRequest('missing', $object, new AnonymizationContext(['tenant' => 'acme']), ['groups' => ['public']]);
        self::assertSame('missing', $request->profile());
        self::assertSame($object, $request->object());
        self::assertSame(['tenant' => 'acme'], $request->context()->attributes());
        self::assertSame(['groups' => ['public']], $request->adapterOptions());

        $this->expectException(ObjectAnonymizationUnavailable::class);
        AnonymizerBuilder::create()->build()->anonymizeObject($request);
    }
}
