<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding;

use PhpAnonymizer\Anonymizer\DataEncoding\YamlEncoder;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use PHPUnit\Framework\TestCase;
use stdClass;

final class YamlEncoderTest extends TestCase
{
    public function testInitializationFailsOnMissingYamlExtension(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->method('extensionIsLoaded')->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new YamlEncoder($dependencyChecker);
    }

    public function testCanDecodeStringIntoArray(): void
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped('The yaml extension is not available.');
        }

        $encoder = new YamlEncoder();
        $data = "---\nfirstName: John\nlastName: Doe\n...\n";

        $decodedData = $encoder->decode($data, new TempStorage());
        self::assertSame(['firstName' => 'John', 'lastName' => 'Doe'], $decodedData);
    }

    public function testWillFailOnDecodeOfNonString(): void
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped('The yaml extension is not available.');
        }

        $encoder = new YamlEncoder();
        $data = ['John Doe'];

        $this->expectException(DataEncodingException::class);
        $encoder->decode($data, new TempStorage());
    }

    public function testWillFailOnDecodeOfMalformedYaml(): void
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped('The yaml extension is not available.');
        }

        $encoder = new YamlEncoder();
        $data = "---\nfirstName: John\nlastName: [ Doe";

        $this->expectException(DataEncodingException::class);
        $encoder->decode($data, new TempStorage());
    }

    public function testCanEncodeArrayIntoYaml(): void
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped('The yaml extension is not available.');
        }

        $encoder = new YamlEncoder();
        $data = ['firstName' => 'John', 'lastName' => 'Doe'];

        $encodedData = $encoder->encode($data, new TempStorage());
        self::assertSame("---\nfirstName: John\nlastName: Doe\n...\n", $encodedData);
    }

    public function testWillFailOnEncodeOnNonArray(): void
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped('The yaml extension is not available.');
        }

        $encoder = new YamlEncoder();
        $data = new stdClass();

        $this->expectException(DataEncodingException::class);

        $encoder->encode($data, new TempStorage());
    }

    public function testCanProvideArrayOverrideDataAccess(): void
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped('The yaml extension is not available.');
        }

        $encoder = new YamlEncoder();
        self::assertSame('array', $encoder->getOverrideDataAccess());
    }

    public function testCanVerifySupportOfString(): void
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped('The yaml extension is not available.');
        }

        $encoder = new YamlEncoder();
        $data = 'string';

        self::assertTrue($encoder->supports($data));
    }

    public function testCanVerifyNonSupportOfNonStrings(): void
    {
        if (!extension_loaded('yaml')) {
            self::markTestSkipped('The yaml extension is not available.');
        }

        $dataArray = [new stdClass(), 1, 1.0, true, null, []];
        $encoder = new YamlEncoder();

        foreach ($dataArray as $data) {
            self::assertFalse($encoder->supports($data));
        }
    }
}
