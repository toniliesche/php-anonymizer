<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding\Provider;

use PhpAnonymizer\Anonymizer\DataEncoding\DataEncoderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\NoOpEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataEncoding\SymfonyEncoder;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataEncoder;
use PhpAnonymizer\Anonymizer\Exception\DataEncoderExistsException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class DefaultDataEncodingProviderTest extends TestCase
{
    public function testCreateWillFailOnSymfonyPackageIsMissingAndNormalizerIsGiven(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new DefaultDataEncodingProvider(
            /** @phpstan-ignore-next-line  */
            new stdClass(),
            null,
            $dependencyChecker,
        );
    }

    public function testCreateWillFailOnSymfonyPackageIsMissingAndDenormalizerIsGiven(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new DefaultDataEncodingProvider(
            null,
            /** @phpstan-ignore-next-line  */
            new stdClass(),
            $dependencyChecker,
        );
    }

    public function testCreateWillFailOnNormalizerDoesNotImplementInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DefaultDataEncodingProvider(
            /** @phpstan-ignore-next-line  */
            new stdClass(),
            new ObjectNormalizer(),
        );
    }

    public function testCreateWillFailOnDenormalizerDoesNotImplementInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DefaultDataEncodingProvider(
            new ObjectNormalizer(),
            /** @phpstan-ignore-next-line  */
            new stdClass(),
        );
    }

    public function testCanTakeNormalizerAndDenormalizerViaSetterInjection(): void
    {
        $provider = new DefaultDataEncodingProvider();

        $objectNormalizer = new ObjectNormalizer();
        $provider->setNormalizer($objectNormalizer);
        $provider->setDenormalizer($objectNormalizer);

        $encoder = $provider->provideEncoder(DataEncoder::SYMFONY->value);
        self::assertInstanceOf(SymfonyEncoder::class, $encoder);
    }

    public function testWillFailOnNormalizerSetterInjectionWhenSymfonyPackageIsMissing(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $provider = new DefaultDataEncodingProvider(
            dependencyChecker: $dependencyChecker,
        );

        $this->expectException(MissingPlatformRequirementsException::class);

        $provider->setNormalizer(new stdClass());
    }

    public function testWillFailOnNormalizerSetterInjectionWhenNormalizerDoesNotImplementInterface(): void
    {
        $provider = new DefaultDataEncodingProvider();

        $this->expectException(InvalidArgumentException::class);

        $provider->setNormalizer(new stdClass());
    }

    public function testWillFailOnDenormalizerSetterInjectionWhenSymfonyPackageIsMissing(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $provider = new DefaultDataEncodingProvider(
            dependencyChecker: $dependencyChecker,
        );

        $this->expectException(MissingPlatformRequirementsException::class);

        $provider->setDenormalizer(new stdClass());
    }

    public function testWillFailOnDenormalizerSetterInjectionWhenDenormalizerDoesNotImplementInterface(): void
    {
        $provider = new DefaultDataEncodingProvider();

        $this->expectException(InvalidArgumentException::class);

        $provider->setDenormalizer(new stdClass());
    }

    public function testCanProvideDataEncoder(): void
    {
        $provider = new DefaultDataEncodingProvider();

        $encoder = $provider->provideEncoder('noop');
        self::assertInstanceOf(NoOpEncoder::class, $encoder);
    }

    public function testWillFailOnProvideUnknownDataEncoder(): void
    {
        $provider = new DefaultDataEncodingProvider();

        $this->expectException(RuntimeException::class);
        $provider->provideEncoder('unknown');
    }

    public function testCanRegisterAndProvideCustomDataEncoder(): void
    {
        $encoder = $this->createMock(DataEncoderInterface::class);

        $provider = new DefaultDataEncodingProvider();
        $provider->registerCustomDataEncoder('custom', $encoder);

        $resolvedEncoder = $provider->provideEncoder('custom');
        self::assertSame($encoder, $resolvedEncoder);
    }

    public function testWillFailOnRegisteringCustomEncoderOnNameConflict(): void
    {
        $encoder = $this->createMock(DataEncoderInterface::class);
        $provider = new DefaultDataEncodingProvider();

        $this->expectException(DataEncoderExistsException::class);
        $provider->registerCustomDataEncoder('noop', $encoder);
    }
}
