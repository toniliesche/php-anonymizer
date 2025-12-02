<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Traits;

use PhpAnonymizer\Anonymizer\DataGeneration\FakerAwareStringGenerator;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class FakerAwareTraitTest extends TestCase
{
    public function testWillFailOnInjectingFakerWhenFakerLibraryIsMissing(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $generator = new FakerAwareStringGenerator(null, $dependencyChecker);
        $this->expectException(MissingPlatformRequirementsException::class);

        /** @phpstan-ignore-next-line */
        $generator->setFaker(new stdClass());
    }

    public function testWillFailOnInjectingFakerWhenInvalidObjectIsGiven(): void
    {
        $generator = new FakerAwareStringGenerator();
        $this->expectException(InvalidArgumentException::class);

        /** @phpstan-ignore-next-line */
        $generator->setFaker(new stdClass());
    }
}
