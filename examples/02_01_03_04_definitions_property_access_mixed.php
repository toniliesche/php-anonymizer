<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use PhpAnonymizer\Anonymizer\Examples\OrderWithSetters;
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
            'name' => 'orders',
            'is_array' => true,
            'children' => [
                [
                    'name' => 'person',
                    'data_access' => 'setter', // add data_access setting here to define access method
                    'children' => [
                        [
                            'name' => 'firstName',
                            'data_access' => 'property', // add data_access setting here to define access method
                        ],
                        [
                            'name' => 'lastName',
                            'data_access' => 'property', // add data_access setting here to define access method
                        ],
                    ],
                ],
            ],
        ],
    ],
);

$person1 = new Person(
    firstName: 'John',
    lastName: 'Doe',
);
$order1 = new OrderWithSetters(
    person: $person1,
);

$person2 = new Person(
    firstName: 'John',
    lastName: 'Doe',
);
$order2 = new OrderWithSetters(
    person: $person2,
);

$data = [
    'orders' => [
        $order1,
        $order2,
    ],
];

$anonymizedData = $anonymizer->run('order', $data);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
