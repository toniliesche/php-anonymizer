<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit;

use PhpAnonymizer\Anonymizer\AnonymizerFactory;
use PHPUnit\Framework\TestCase;
use function Safe\file_get_contents;
use function Safe\yaml_parse;
use function sprintf;

final class AnonymizerFactoryTest extends TestCase
{
    public function testCanCreateAnonymizer(): void
    {
        $this->expectNotToPerformAssertions();
        $configString = file_get_contents(sprintf('%s/config/complete.yaml', FIXTURES_ROOT));
        $configArray = yaml_parse($configString);

        $factory = new AnonymizerFactory(
            dataOptions: $configArray['anonymizer']['data'],
            parserOptions: $configArray['anonymizer']['parsers'],
            rules: [],
            ruleProviders: [],
        );

        $factory->create();
    }

    public function testCanCreateAnonymizerWithRule(): void
    {
        $this->expectNotToPerformAssertions();
        $configString = file_get_contents(sprintf('%s/config/complete_with_rules.yaml', FIXTURES_ROOT));
        $configArray = yaml_parse($configString);

        $factory = new AnonymizerFactory(
            dataOptions: $configArray['anonymizer']['data'],
            parserOptions: $configArray['anonymizer']['parsers'],
            rules: $configArray['anonymizer']['rules'],
            ruleProviders: [],
        );

        $factory->create();
    }
}
