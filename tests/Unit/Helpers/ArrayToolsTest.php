<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Helpers;

use PhpAnonymizer\Anonymizer\Enum\ArrayType;
use PhpAnonymizer\Anonymizer\Helpers\ArrayTools;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ArrayToolsTest extends TestCase
{
    public function testDetectTypeIdentifiesListArrays(): void
    {
        $data = ['first', 'second'];

        self::assertSame(ArrayType::LIST, ArrayTools::detectType($data));
    }

    public function testDetectTypeIdentifiesMapArrays(): void
    {
        $data = [
            'first' => 'John',
            'last' => 'Doe',
        ];

        self::assertSame(ArrayType::MAP, ArrayTools::detectType($data));
    }

    public function testDetectTypeIdentifiesMixedKeyArrays(): void
    {
        $data = [
            'first' => 'John',
            2 => 'Doe',
        ];

        self::assertSame(ArrayType::MIXED, ArrayTools::detectType($data));
    }

    public function testStringOnlyArrayChecks(): void
    {
        self::assertTrue(ArrayTools::isStringOnlyArray([]));
        self::assertTrue(ArrayTools::isStringOnlyArray(['John', 'Doe']));
        self::assertFalse(ArrayTools::isStringOnlyArray(['John', 12]));
    }

    public function testObjectOnlyArrayChecks(): void
    {
        self::assertTrue(ArrayTools::isObjectOnlyArray([]));
        self::assertTrue(ArrayTools::isObjectOnlyArray([new stdClass(), new stdClass()]));
        self::assertFalse(ArrayTools::isObjectOnlyArray([new stdClass(), 'not-object']));
    }

    public function testArrayOnlyArrayChecks(): void
    {
        self::assertTrue(ArrayTools::isArrayOnlyArray([]));
        self::assertTrue(ArrayTools::isArrayOnlyArray([['a'], ['b']]));
        self::assertFalse(ArrayTools::isArrayOnlyArray([['a'], 'not-array']));
    }

    public function testScalarOnlyArrayChecks(): void
    {
        self::assertTrue(ArrayTools::isScalarOnlyArray([]));
        self::assertTrue(ArrayTools::isScalarOnlyArray(['a', 1, 1.2, true]));
        self::assertFalse(ArrayTools::isScalarOnlyArray(['a', ['nested']]));
    }
}
