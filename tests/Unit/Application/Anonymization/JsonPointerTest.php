<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonPointer::class)]
#[CoversClass(AnonymizationReport::class)]
#[CoversClass(AnonymizationResult::class)]
final class JsonPointerTest extends TestCase
{
    public function testEscapesSegmentsAndPrefixesNestedReports(): void
    {
        $base = JsonPointer::root()->append('a/b~c');
        self::assertSame('/a~1b~0c/0', (string) $base->append(0));
        self::assertSame('/a~1b~0c', (string) JsonPointer::fromString('/a~1b~0c'));

        $report = new AnonymizationReport();
        $report->merge(new AnonymizationResult([], ['/email'], ['/missing']), $base);

        self::assertSame(['/a~1b~0c/email'], $report->anonymizedPaths());
        self::assertSame(['/a~1b~0c/missing'], $report->missingPaths());
    }
}
