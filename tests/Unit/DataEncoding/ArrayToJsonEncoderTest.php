<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding;

use PhpAnonymizer\Anonymizer\DataEncoding\ArrayToJsonEncoder;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use PHPUnit\Framework\TestCase;
use stdClass;

class ArrayToJsonEncoderTest extends TestCase
{
    public function testWillFailOnInitializationWhenJsonExceptionIsMissing(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->method('extensionIsLoaded')->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new ArrayToJsonEncoder($dependencyChecker);
    }

    public function testCanProcessArray(): void
    {
        $encoder = new ArrayToJsonEncoder();
        $data = ['firstName' => 'John', 'lastName' => 'Doe'];

        $decodedData = $encoder->decode($data, new TempStorage());
        self::assertSame(['firstName' => 'John', 'lastName' => 'Doe'], $decodedData);
    }

    public function testWillFailOnDecodeOnNonArray(): void
    {
        $encoder = new ArrayToJsonEncoder();
        $data = '{"firstName":"John","lastName":"Doe"}';

        $this->expectException(DataEncodingException::class);
        $encoder->decode($data, new TempStorage());
    }

    public function testCanEncodeArrayIntoJsonString(): void
    {
        $encoder = new ArrayToJsonEncoder();
        $data = ['firstName' => 'John', 'lastName' => 'Doe'];

        $encodedData = $encoder->encode($data, new TempStorage());
        self::assertSame('{"firstName":"John","lastName":"Doe"}', $encodedData);
    }

    public function testWillFailOnEncodeOnNonArray(): void
    {
        $encoder = new ArrayToJsonEncoder();
        $data = new stdClass();

        $this->expectException(DataEncodingException::class);

        $encoder->encode($data, new TempStorage());
    }

    public function testWillFailOnEncodeOnInvalidArrayData(): void
    {
        $encoder = new ArrayToJsonEncoder();
        $data = ['firstName' => 'John', 'lastName' => 'Doe', 'age' => INF];

        $this->expectException(DataEncodingException::class);
        $encoder->encode($data, new TempStorage());
    }

    public function testCanProvideArrayOverrideDataAccess(): void
    {
        $encoder = new ArrayToJsonEncoder();
        self::assertSame('array', $encoder->getOverrideDataAccess());
    }

    public function testCanVerifySupportOfArray(): void
    {
        $encoder = new ArrayToJsonEncoder();
        $data = ['firstName' => 'John', 'lastName' => 'Doe'];

        self::assertTrue($encoder->supports($data));
    }

    public function testCanVerifyNonSupportOfNonArray(): void
    {
        $dataArray = [new stdClass(), 1, 1.0, true, null, 'string'];
        $encoder = new ArrayToJsonEncoder();

        foreach ($dataArray as $data) {
            self::assertFalse($encoder->supports($data));
        }
    }
}
