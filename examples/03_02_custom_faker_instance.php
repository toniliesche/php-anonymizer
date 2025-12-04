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
    // pass custom Faker instance here
    ->withCustomFaker($faker)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'nodes' => [
            [
                'name' => 'order',
                'children' => [
                    [
                        'name' => 'person',
                        'children' => [
                            [
                                'name' => 'first_name',
                                'value_type' => 'firstName',
                            ],
                            [
                                'name' => 'last_name',
                                'value_type' => 'lastName',
                            ],
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
            'first_name' => 'John',
            'last_name' => 'Doe',
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
