<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        // the [] in front of orders mark this layer to be a list of person objects
        '[]orders.person.first_name',
        '[]orders.person.last_name',
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
