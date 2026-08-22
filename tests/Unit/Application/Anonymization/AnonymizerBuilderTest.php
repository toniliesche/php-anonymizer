<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFacade;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ObjectAnonymizationRequest;
use PhpAnonymizer\Anonymizer\Application\Exception\ObjectAnonymizationUnavailable;
use PhpAnonymizer\Anonymizer\Application\Extension\AnonymizerExtensionInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\ObjectAnonymizerInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
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
        $provider = new class () implements ProfileProviderInterface {
            #[Override]
            public function profiles(): iterable
            {
                yield ProfileDefinition::fromArray('copy.provider', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
            }
        };
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
        $provider = new readonly class ($providerProfile) implements ProfileProviderInterface {
            public function __construct(private ProfileDefinition $profile)
            {
            }

            #[Override]
            public function profiles(): iterable
            {
                yield $this->profile;
            }
        };
        $strategy = new class () implements ReplacementStrategyInterface {
            #[Override]
            public function id(): string
            {
                return 'builder.strategy';
            }

            #[Override]
            public function replace(string $value): string
            {
                return $value . '-safe';
            }
        };
        $codec = new class () implements PayloadCodecInterface {
            #[Override]
            public function id(): string
            {
                return 'builder.codec';
            }

            #[Override]
            public function decode(string $payload): array
            {
                return ['value' => $payload];
            }

            #[Override]
            public function encode(array $data): string
            {
                $value = $data['value'] ?? '';

                return is_string($value) ? $value : '';
            }
        };
        $transformation = new class () implements ValueTransformationInterface {
            #[Override]
            public function id(): string
            {
                return 'builder.transformation';
            }

            #[Override]
            public function supportedTypes(): array
            {
                return ['string'];
            }

            #[Override]
            public function outputType(): string
            {
                return 'string';
            }

            #[Override]
            public function validateOptions(array $options): void
            {
            }

            #[Override]
            public function transform(mixed $value, array $options): mixed
            {
                return $value;
            }
        };
        $adapter = self::createStub(ObjectAnonymizerInterface::class);
        $extension = new readonly class ($extensionProfile) implements AnonymizerExtensionInterface {
            public function __construct(private ProfileDefinition $profile)
            {
            }

            #[Override]
            public function configure(AnonymizerBuilder $builder): AnonymizerBuilder
            {
                return $builder->withProfile($this->profile);
            }
        };

        $original = new AnonymizerBuilder();
        $builder = $original
            ->withProfile($profile)
            ->withProfiles([$profileTwo])
            ->withProfileProvider($provider)
            ->withProfileProviders([new class () implements ProfileProviderInterface {
                #[Override]
                public function profiles(): iterable
                {
                    yield ProfileDefinition::fromArray('provider.two', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
                }
            }])
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
        $provider = new class () implements ProfileProviderInterface {
            #[Override]
            public function profiles(): iterable
            {
                yield ProfileDefinition::fromArray('generated', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
            }
        };
        $builder = AnonymizerBuilder::create()->withProfileProviders((static function () use ($provider): iterable { yield $provider; })());

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
