<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->build();

$anonymizer->registerRuleSet(
    'person-data',
    [
        'order.person.first_name',
        'order.person.last_name',
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

$anonymizedData = $anonymizer->run('person-data', $data);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
