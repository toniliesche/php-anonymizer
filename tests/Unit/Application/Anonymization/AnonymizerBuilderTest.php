<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFacade;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ObjectAnonymizationRequest;
use PhpAnonymizer\Anonymizer\Application\Exception\ObjectAnonymizationUnavailable;
use PhpAnonymizer\Anonymizer\Application\Extension\ObjectAnonymizerInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\ProfileProviderSequence;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticAnonymizerExtension;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticPayloadCodec;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticProfileProvider;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticReplacementStrategy;
use PhpAnonymizer\Anonymizer\Tests\Support\Extension\StaticValueTransformation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversNothing]
#[AllowMockObjectsWithoutExpectations]
final class AnonymizerBuilderTest extends TestCase
{
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
            ->withPayloadCodecs([])
            ->withValueTransformation($transformation)
            ->withValueTransformations([])
            ->withLimits(new AnonymizationLimits(maxDepth: 8))
            ->withObjectAnonymizer($adapter)
            ->withExtension($extension);

        self::assertNotSame($original, $builder);
        self::assertInstanceOf(AnonymizerFacade::class, $builder->build());
    }

    public function testItMaterializesGeneratorsAndRejectsInvalidProviders(): void
    {
        $provider = new StaticProfileProvider([ProfileDefinition::fromArray('generated', ['mode' => 'redact', 'rules' => [['field' => 'email']]])]);
        $builder = AnonymizerBuilder::create()->withProfileProviders(new ProfileProviderSequence([$provider]));

        self::assertSame(['email' => '[REDACTED]'], $builder->build()->anonymize('generated', ['email' => 'secret']));

        $this->expectException(InvalidArgumentException::class);
        AnonymizerBuilder::create()->withProfileProviders([new stdClass()]);
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
