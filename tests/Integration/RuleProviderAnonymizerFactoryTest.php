<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Integration;

use PhpAnonymizer\Anonymizer\AnonymizerFactory;
use PhpAnonymizer\Anonymizer\Test\Helper\RuleProvider\AddressRuleProvider;
use PHPUnit\Framework\TestCase;
use function Safe\file_get_contents;
use function Safe\yaml_parse;
use function sprintf;

final class RuleProviderAnonymizerFactoryTest extends TestCase
{
    public function testFactoryRegistersRulesFromProviders(): void
    {
        $config = yaml_parse(file_get_contents(sprintf('%s/config/complete.yaml', FIXTURES_ROOT)));

        $ruleProvider = new AddressRuleProvider();

        $factory = new AnonymizerFactory(
            dataOptions: $config['anonymizer']['data'],
            parserOptions: $config['anonymizer']['parsers'],
            rules: [],
            ruleProviders: [$ruleProvider],
        );

        $anonymizer = $factory->create();
        $data = [
            'address' => [
                'name' => 'John Doe',
                'city' => 'New York',
            ],
        ];

        $anonymized = $anonymizer->run('address', $data);

        self::assertSame('********', $anonymized['address']['name']);
        self::assertSame('New York', $anonymized['address']['city']);
    }
}
