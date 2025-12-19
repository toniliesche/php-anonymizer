<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataGeneration;

use Generator;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class StarMaskedStringGeneratorTest extends TestCase
{
    #[DataProvider('provideSupportCases')]
    public function testSupportsValue(mixed $value, bool $expected): void
    {
        $dataGenerator = new StarMaskedStringGenerator();

        self::assertSame($expected, $dataGenerator->supports($value, null));
    }

    public function testCanHideData(): void
    {
        $dataGenerator = new StarMaskedStringGenerator();

        $string = 'Hello World';
        $anonymizedString = $dataGenerator->generate(['test'], $string, null);

        self::assertSame('***********', $anonymizedString);
    }

    #[DataProvider('provideInvalidValues')]
    public function testWillFailOnHideDataOnNonStrings(mixed $value): void
    {
        $dataGenerator = new StarMaskedStringGenerator();

        $this->expectException(InvalidObjectTypeException::class);

        $dataGenerator->generate(['test'], $value, null);
    }

    public static function provideSupportCases(): Generator
    {
        yield 'string' => ['Hello World', true];

        yield 'int' => [42, false];

        yield 'array' => [['john'], false];

        yield 'object' => [new stdClass(), false];
    }

    public static function provideInvalidValues(): Generator
    {
        yield 'int' => [123];

        yield 'object' => [new stdClass()];
    }
}
