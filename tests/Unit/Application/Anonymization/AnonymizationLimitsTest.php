<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnonymizationLimits::class)]
final class AnonymizationLimitsTest extends TestCase
{
    public function testDefaultsAreStable(): void
    {
        $limits = new AnonymizationLimits();
        self::assertSame(10_485_760, $limits->maxInputBytes);
        self::assertSame(64, $limits->maxDepth);
        self::assertSame(100_000, $limits->maxVisitedNodes);
        self::assertSame(50_000, $limits->maxCollectionEntries);
        self::assertSame(8, $limits->maxEmbeddedPayloadDepth);
    }

    public function testEveryLimitMustBePositive(): void
    {
        foreach (range(0, 4) as $index) {
            $values = [1, 1, 1, 1, 1];
            $values[$index] = 0;

            try {
                new AnonymizationLimits(...$values);
                self::fail('Expected zero limit to be rejected.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('must be greater than zero', $exception->getMessage());
            }
        }
    }
}
