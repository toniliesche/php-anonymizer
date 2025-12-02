<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataGeneration;

use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PHPUnit\Framework\TestCase;
use stdClass;

class StarMaskedStringGeneratorTest extends TestCase
{
    public function testCanVerifySupportOfStrings(): void
    {
        $dataGenerator = new StarMaskedStringGenerator();

        self::assertTrue($dataGenerator->supports('Hello World', null));
    }

    public function testCanVerifyNonSupportOfObjects(): void
    {
        $dataGenerator = new StarMaskedStringGenerator();

        self::assertFalse($dataGenerator->supports(new stdClass(), null));
    }

    public function testCanHideData(): void
    {
        $dataGenerator = new StarMaskedStringGenerator();

        $string = 'Hello World';
        $anonymizedString = $dataGenerator->generate(['test'], $string, null);

        self::assertSame('***********', $anonymizedString);
    }

    public function testWillFailOnHideDataOnNonStrings(): void
    {
        $dataGenerator = new StarMaskedStringGenerator();

        $this->expectException(InvalidObjectTypeException::class);

        /** @phpstan-ignore-next-line  */
        $dataGenerator->generate(['test'], new stdClass(), null);
    }
}
