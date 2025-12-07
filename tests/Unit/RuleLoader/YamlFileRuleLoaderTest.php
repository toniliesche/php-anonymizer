<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\RuleLoader;

use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\FileNotFoundException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\RuleLoader\YamlFileRuleLoader;
use PHPUnit\Framework\TestCase;
use function sprintf;

final class YamlFileRuleLoaderTest extends TestCase
{
    public function testCanCreate(): void
    {
        $this->expectNotToPerformAssertions();
        new YamlFileRuleLoader(
            filePath: sprintf('%s/rules/rules.yaml', FIXTURES_ROOT),
        );
    }

    public function testCreateWillFailOnMissingYamlExtension(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->method('extensionIsLoaded')->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new YamlFileRuleLoader(
            filePath: sprintf('%s/rules/rules.yaml', FIXTURES_ROOT),
            dependencyChecker: $dependencyChecker,
        );
    }

    public function testCanLoadRules(): void
    {
        $loader = new YamlFileRuleLoader(
            filePath: sprintf('%s/rules/rules.yaml', FIXTURES_ROOT),
        );

        $generator = $loader->loadRules();
        $rules = iterator_to_array($generator);

        self::assertCount(1, $rules);
    }

    public function testLoadRulesWillFailOnNonExistingFile(): void
    {
        $loader = new YamlFileRuleLoader(
            filePath: sprintf('%s/rules/rules_non_existing.yaml', FIXTURES_ROOT),
        );

        $this->expectException(FileNotFoundException::class);
        $generator = $loader->loadRules();
        iterator_to_array($generator);
    }
}
