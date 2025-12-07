<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\RuleLoader;

use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\FileNotFoundException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\RuleLoader\JsonFileRuleLoader;
use PHPUnit\Framework\TestCase;
use function sprintf;

final class JsonFileRuleLoaderTest extends TestCase
{
    public function testCanCreate(): void
    {
        $this->expectNotToPerformAssertions();
        new JsonFileRuleLoader(
            filePath: sprintf('%s/rules/rules.json', FIXTURES_ROOT),
        );
    }

    public function testCreateWillFailOnMissingJsonExtension(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->method('extensionIsLoaded')->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new JsonFileRuleLoader(
            filePath: sprintf('%s/rules/rules.json', FIXTURES_ROOT),
            dependencyChecker: $dependencyChecker,
        );
    }

    public function testCanLoadRules(): void
    {
        $loader = new JsonFileRuleLoader(
            filePath: sprintf('%s/rules/rules.json', FIXTURES_ROOT),
        );

        $generator = $loader->loadRules();
        $rules = iterator_to_array($generator);

        self::assertCount(1, $rules);
    }

    public function testLoadRulesWillFailOnNonExistingFile(): void
    {
        $loader = new JsonFileRuleLoader(
            filePath: sprintf('%s/rules/rules_non_existing.json', FIXTURES_ROOT),
        );

        $this->expectException(FileNotFoundException::class);
        $generator = $loader->loadRules();
        iterator_to_array($generator);
    }
}
