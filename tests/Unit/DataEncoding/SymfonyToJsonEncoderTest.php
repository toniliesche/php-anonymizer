<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding;

use PhpAnonymizer\Anonymizer\DataEncoding\SymfonyToJsonEncoder;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Address;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class SymfonyToJsonEncoderTest extends TestCase
{
    public function testCreateWillFailOnJsonExtensionIsMissing(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->expects($this->once())
            ->method('extensionIsLoaded')
            ->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new SymfonyToJsonEncoder(
            /** @phpstan-ignore-next-line */
            new stdClass(),
            $dependencyChecker,
        );
    }

    public function testCreateWillFailOnSymfonyPackageIsMissing(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->expects($this->once())
            ->method('extensionIsLoaded')
            ->willReturn(true);
        $dependencyChecker->expects($this->once())
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new SymfonyToJsonEncoder(
            /** @phpstan-ignore-next-line */
            new stdClass(),
            $dependencyChecker,
        );
    }

    public function testCreateWillFailOnNormalizerDoesNotImplementInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SymfonyToJsonEncoder(
            /** @phpstan-ignore-next-line  */
            new stdClass(),
        );
    }

    public function testCanDecodeObjectInToJson(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToJsonEncoder(
            $objectNormalizer,
        );
        $data = new Address(
            'John Doe',
            'New York',
        );

        $encodedData = $encoder->decode($data, new TempStorage());
        self::assertEquals(['name' => 'John Doe', 'city' => 'New York'], $encodedData);
    }

    public function testWillFailOnDecodeOnNonObject(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToJsonEncoder(
            $objectNormalizer,
        );
        $data = ['John Doe'];

        $this->expectException(DataEncodingException::class);
        $encoder->decode($data, new TempStorage());
    }

    public function testCanEncodeArrayIntoObject(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToJsonEncoder(
            $objectNormalizer,
        );
        $data = [
            'name' => 'John Doe',
            'city' => 'New York',
        ];

        $encodedData = $encoder->encode($data, (new TempStorage())->store('symfony-encoder-type', Address::class));
        self::assertIsString($encodedData);

        self::assertSame('{"name":"John Doe","city":"New York"}', $encodedData);
    }

    public function testWillFailOnEncodeOnNonArray(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToJsonEncoder(
            $objectNormalizer,
        );
        $data = new Address(
            'John Doe',
            'New York',
        );

        $this->expectException(DataEncodingException::class);

        $encoder->encode($data, new TempStorage());
    }

    public function testCanProvideArrayOverrideAccess(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToJsonEncoder(
            $objectNormalizer,
        );
        self::assertSame('array', $encoder->getOverrideDataAccess());
    }

    public function testCanVerifySupportOfObject(): void
    {
        $encoder = new SymfonyToJsonEncoder(
            new ObjectNormalizer(),
        );
        $data = new stdClass();

        self::assertTrue($encoder->supports($data));
    }

    public function testCanVerifyNonSupportOfNonObjects(): void
    {
        $dataArray = ['string', 1, 1.0, true, null, []];
        $encoder = new SymfonyToJsonEncoder(
            new ObjectNormalizer(),
        );

        foreach ($dataArray as $data) {
            self::assertFalse($encoder->supports($data));
        }
    }
}
