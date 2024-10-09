<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding;

use PhpAnonymizer\Anonymizer\DataEncoding\SymfonyToArrayEncoder;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Address;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SymfonyToArrayEncoderTest extends TestCase
{
    public function testWillFailOnInitializationWhenSymfonyPackageIsMissing(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->method('libraryIsInstalled')->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new SymfonyToArrayEncoder(
            /** @phpstan-ignore-next-line */
            new stdClass(),
            $dependencyChecker,
        );
    }

    public function testWillFailOnInitializationWhenNormalizerDoesNotImplementInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SymfonyToArrayEncoder(
            /** @phpstan-ignore-next-line  */
            new stdClass(),
        );
    }

    public function testCanDecodeObjectIntoArray(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToArrayEncoder(
            $objectNormalizer,
        );
        $data = new Address(
            'John Doe',
            'New York',
        );

        $encodedData = $encoder->decode($data, new TempStorage());
        $this->assertEquals(['name' => 'John Doe', 'city' => 'New York'], $encodedData);
    }

    public function testWillFailOnDecodeOnNonObject(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToArrayEncoder(
            $objectNormalizer,
        );
        $data = ['John Doe'];

        $this->expectException(DataEncodingException::class);
        $encoder->decode($data, new TempStorage());
    }

    public function testCanEncodeArrayIntoObject(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToArrayEncoder(
            $objectNormalizer,
        );
        $data = [
            'name' => 'John Doe',
            'city' => 'New York',
        ];

        $encodedData = $encoder->encode($data, (new TempStorage())->store('symfony-encoder-type', Address::class));
        $this->assertIsArray($encodedData);

        $this->assertSame('John Doe', $encodedData['name']);
        $this->assertSame('New York', $encodedData['city']);
    }

    public function testWillFailOnEncodeOnNonArray(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToArrayEncoder(
            $objectNormalizer,
        );
        $data = new Address(
            'John Doe',
            'New York',
        );

        $this->expectException(DataEncodingException::class);

        /** @phpstan-ignore-next-line  */
        $encoder->encode($data, new TempStorage());
    }

    public function testCanProvideArrayOverrideAccess(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $encoder = new SymfonyToArrayEncoder(
            $objectNormalizer,
        );
        $this->assertSame('array', $encoder->getOverrideDataAccess());
    }

    public function testCanVerifySupportOfObject(): void
    {
        $encoder = new SymfonyToArrayEncoder(
            new ObjectNormalizer(),
        );
        $data = new stdClass();

        $this->assertTrue($encoder->supports($data));
    }

    public function testCanVerifyNonSupportOfNonObjects(): void
    {
        $dataArray = ['string', 1, 1.0, true, null, []];
        $encoder = new SymfonyToArrayEncoder(
            new ObjectNormalizer(),
        );

        foreach ($dataArray as $data) {
            $this->assertFalse($encoder->supports($data));
        }
    }
}
