<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Faker\Factory;
use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use PhpAnonymizer\Anonymizer\Examples\OrderWithJsonAddress;

$faker = Factory::create('de_DE');
$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->withCustomFaker($faker)
    ->withFakerSeed('codeword')
    ->build();

$anonymizer->registerRuleSet(
    'order',
    [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'address',
                    'data_access' => 'property',
                    'nested_rule' => 'address',
                    'nested_type' => 'json',
                ],
            ],
        ],
    ],
);

$anonymizer->registerRuleSet(
    'address',
    [
        'nodes' => [
            [
                'name' => 'firstName',
            ],
            [
                'name' => 'lastName',
            ],
        ],
    ],
);

$data = [
    'order' => new OrderWithJsonAddress(
        address: '{"firstName":"John","lastName":"Doe"}',
    ),
];

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
