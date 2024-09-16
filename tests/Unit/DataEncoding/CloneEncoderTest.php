<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding;

use PHPUnit\Framework\TestCase;
use stdClass;
use PhpAnonymizer\Anonymizer\DataEncoding\CloneEncoder;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Address;

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

        $this->assertEquals($data, $decodedData);
        $this->assertNotSame($data, $decodedData);
    }

    public function testCanPassObjectViaReferenceWithoutChangeOnEncode(): void
    {
        $encoder = new CloneEncoder();
        $data = new Address(
            'John Doe',
            'New York',
        );

        $encodedData = $encoder->encode($data, new TempStorage());
        $this->assertSame($data, $encodedData);
    }

    public function testCanProvideNullOverrideDataAccess(): void
    {
        $encoder = new CloneEncoder();
        $this->assertNull($encoder->getOverrideDataAccess());
    }

    public function testCanVerifySupportOfDataTypes(): void
    {
        $dataArray = [new stdClass(), 'string', 1, 1.0, true, null, []];
        $encoder = new CloneEncoder();

        foreach ($dataArray as $data) {
            $this->assertTrue($encoder->supports($data));
        }
    }
}
