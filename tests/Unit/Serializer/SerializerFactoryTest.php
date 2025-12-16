<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Serializer;

use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Serializer\SerializerFactory;
use PHPUnit\Framework\TestCase;
use function Safe\file_get_contents;
use function Safe\yaml_parse;

class SerializerFactoryTest extends TestCase
{
    public function testCreateWillFailOnMissingSymfonyPackage(): void
    {
        $configString = file_get_contents(sprintf('%s/config/complete.yaml', FIXTURES_ROOT));
        $configArray = yaml_parse($configString);

        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->expects($this->once())
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new SerializerFactory(
            $configArray['anonymizer']['serializer'],
            $dependencyChecker,
        );
    }

    public function testCanCreate(): void
    {
        $configString = file_get_contents(sprintf('%s/config/complete.yaml', FIXTURES_ROOT));
        $configArray = yaml_parse($configString);

        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->expects($this->once())
            ->method('libraryIsInstalled')
            ->willReturn(true);

        $factory = new SerializerFactory(
            $configArray['anonymizer']['serializer'],
            $dependencyChecker,
        );

        $factory->create();
    }
}
