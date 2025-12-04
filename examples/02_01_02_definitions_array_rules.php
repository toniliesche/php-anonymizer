<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'orders',
            'is_array' => true, // mark this layer to be a list of person objects
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'first_name',
                        ],
                        [
                            'name' => 'last_name',
                        ],
                    ],
                ],
            ],
        ],
    ],
);

$data = [
    'orders' => [
        [
            'person' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
        ],
        [
            'person' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
            ],
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
