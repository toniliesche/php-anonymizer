<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding;

use PhpAnonymizer\Anonymizer\DataEncoding\JsonEncoder;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use PHPUnit\Framework\TestCase;
use stdClass;

class JsonEncoderTest extends TestCase
{
    public function testWillFailOnInitializationWhenJsonExceptionIsMissing(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->method('extensionIsLoaded')->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new JsonEncoder($dependencyChecker);
    }

    public function testCanDecodeJsonStringIntoArray(): void
    {
        $encoder = new JsonEncoder();
        $data = '{"firstName":"John","lastName":"Doe"}';

        $decodedData = $encoder->decode($data, new TempStorage());
        $this->assertSame(['firstName' => 'John', 'lastName' => 'Doe'], $decodedData);
    }

    public function testWillFailOnDecodeOnNonString(): void
    {
        $encoder = new JsonEncoder();
        $data = ['John Doe'];

        $this->expectException(DataEncodingException::class);
        $encoder->decode($data, new TempStorage());
    }

    public function testWillFailOnDecodeOnMalformedJson(): void
    {
        $encoder = new JsonEncoder();
        $data = '{"firstName":"John","lastName":"Doe"';

        $this->expectException(DataEncodingException::class);
        $encoder->decode($data, new TempStorage());
    }

    public function testCanEncodeArrayIntoJsonString(): void
    {
        $encoder = new JsonEncoder();
        $data = ['firstName' => 'John', 'lastName' => 'Doe'];

        $encodedData = $encoder->encode($data, new TempStorage());
        $this->assertSame('{"firstName":"John","lastName":"Doe"}', $encodedData);
    }

    public function testWillFailOnEncodeOnNonArray(): void
    {
        $encoder = new JsonEncoder();
        $data = new stdClass();

        $this->expectException(DataEncodingException::class);

        /** @phpstan-ignore-next-line */
        $encoder->encode($data, new TempStorage());
    }

    public function testWillFailOnEncodeOnInvalidArrayData(): void
    {
        $encoder = new JsonEncoder();
        $data = ['firstName' => 'John', 'lastName' => 'Doe', 'age' => INF];

        $this->expectException(DataEncodingException::class);
        $encoder->encode($data, new TempStorage());
    }

    public function testCanProvideArrayOverrideDataAccess(): void
    {
        $encoder = new JsonEncoder();
        $this->assertSame('array', $encoder->getOverrideDataAccess());
    }

    public function testCanVerifySupportOfString(): void
    {
        $encoder = new JsonEncoder();
        $data = 'string';

        $this->assertTrue($encoder->supports($data));
    }

    public function testCanVerifyNonSupportOfNonStrings(): void
    {
        $dataArray = [new stdClass(), 1, 1.0, true, null, []];
        $encoder = new JsonEncoder();

        foreach ($dataArray as $data) {
            $this->assertFalse($encoder->supports($data));
        }
    }
}
