<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataEncoding;

use PhpAnonymizer\Anonymizer\DataEncoding\NoOpEncoder;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use PHPUnit\Framework\TestCase;
use stdClass;

final class NoOpEncoderTest extends TestCase
{
    public function testCanPassObjectViaReferenceWithoutChangeOnDecode(): void
    {
        $encoder = new NoOpEncoder();

        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $decodedData = $encoder->decode($data, new TempStorage());
        self::assertSame($data, $decodedData);
    }

    public function testCanPassObjectViaReferenceWithoutChangeOnEncode(): void
    {
        $encoder = new NoOpEncoder();

        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $encodedData = $encoder->encode($data, new TempStorage());
        self::assertSame($data, $encodedData);
    }

    public function testCanProvideNullOverrideDataAccess(): void
    {
        $encoder = new NoOpEncoder();
        self::assertNull($encoder->getOverrideDataAccess());
    }

    public function testCanVerifySupportOfDataTypes(): void
    {
        $dataArray = [new stdClass(), 'string', 1, 1.0, true, null, []];
        $encoder = new NoOpEncoder();

        foreach ($dataArray as $data) {
            self::assertTrue($encoder->supports($data));
        }
    }
}
