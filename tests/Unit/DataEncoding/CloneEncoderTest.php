<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding;

use PhpAnonymizer\Anonymizer\DataEncoding\CloneEncoder;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Address;
use PHPUnit\Framework\TestCase;
use stdClass;

class CloneEncoderTest extends TestCase
{
    public function testCanCloneObjectIntoNewInstanceOnDecode(): void
    {
        $encoder = new CloneEncoder();
        $data = new Address(
            'John Doe',
            'New York',
        );

        $decodedData = $encoder->decode($data, new TempStorage());

        self::assertEquals($data, $decodedData);
        self::assertNotSame($data, $decodedData);
    }

    public function testCanPassObjectViaReferenceWithoutChangeOnEncode(): void
    {
        $encoder = new CloneEncoder();
        $data = new Address(
            'John Doe',
            'New York',
        );

        $encodedData = $encoder->encode($data, new TempStorage());
        self::assertSame($data, $encodedData);
    }

    public function testCanProvideNullOverrideDataAccess(): void
    {
        $encoder = new CloneEncoder();
        self::assertNull($encoder->getOverrideDataAccess());
    }

    public function testCanVerifySupportOfDataTypes(): void
    {
        $dataArray = [new stdClass(), 'string', 1, 1.0, true, null, []];
        $encoder = new CloneEncoder();

        foreach ($dataArray as $data) {
            self::assertTrue($encoder->supports($data));
        }
    }
}
