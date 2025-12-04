<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Faker\Factory;
use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;

$faker = Factory::create('de_DE');
$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->withCustomFaker($faker)
    ->withFakerSeed('codeword')
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'firstName',
                            'value_type' => 'firstName', // add value_type option to define faker value to be used
                        ],
                        [
                            'name' => 'lastName',
                            'value_type' => 'lastName', // add value_type option to define faker value to be used
                        ],
                    ],
                ],
            ],
        ],
    ],
);

$data = [
    'order' => [
        'person' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
        ],
    ],
];

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
