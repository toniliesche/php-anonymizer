<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use PhpAnonymizer\Anonymizer\Examples\Person;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
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
                            'data_access' => 'property', // add data_access setting here to define access method
                        ],
                        [
                            'name' => 'lastName',
                            'data_access' => 'property',
                        ],
                    ],
                ],
            ],
        ],
    ],
);

$person = new Person(
    firstName: 'John',
    lastName: 'Doe',
);

$data = [
    'order' => [
        'person' => $person,
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
