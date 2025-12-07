<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit;

use PhpAnonymizer\Anonymizer\AnonymizerFactory;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PHPUnit\Framework\TestCase;
use function Safe\file_get_contents;
use function Safe\yaml_parse;
use function sprintf;

final class AnonymizerFactoryTest extends TestCase
{
    public function testCanCreateAnonymizer(): void
    {
        $configString = file_get_contents(sprintf('%s/config/complete.yaml', FIXTURES_ROOT));
        $configArray = yaml_parse($configString);

        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->expects($this->once())
            ->method('libraryIsInstalled')
            ->willReturn(true);

        $factory = new AnonymizerFactory(
            dataOptions: $configArray['anonymizer']['data'],
            parserOptions: $configArray['anonymizer']['parsers'],
            rules: [],
            serializerOptions: $configArray['anonymizer']['serializer'],
            dependencyChecker: $dependencyChecker,
        );

        $factory->create();
    }

    public function testCanCreateAnonymizerWithRule(): void
    {
        $configString = file_get_contents(sprintf('%s/config/complete_with_rules.yaml', FIXTURES_ROOT));
        $configArray = yaml_parse($configString);

        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->expects($this->once())
            ->method('libraryIsInstalled')
            ->willReturn(true);

        $factory = new AnonymizerFactory(
            dataOptions: $configArray['anonymizer']['data'],
            parserOptions: $configArray['anonymizer']['parsers'],
            rules: $configArray['anonymizer']['rules'],
            serializerOptions: $configArray['anonymizer']['serializer'],
            dependencyChecker: $dependencyChecker,
        );

        $factory->create();
    }

    public function testCreateWithSerializerWillFailOnMissingSymfonyPackage(): void
    {
        $configString = file_get_contents(sprintf('%s/config/complete.yaml', FIXTURES_ROOT));
        $configArray = yaml_parse($configString);

        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->expects($this->once())
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $factory = new AnonymizerFactory(
            dataOptions: $configArray['anonymizer']['data'],
            parserOptions: $configArray['anonymizer']['parsers'],
            rules: [],
            serializerOptions: $configArray['anonymizer']['serializer'],
            dependencyChecker: $dependencyChecker,
        );

        $this->expectException(MissingPlatformRequirementsException::class);
        $factory->create();
    }
}
