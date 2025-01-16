<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->build();

$anonymizer->registerRuleSet(
    'order',
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

$anonymizedData = $anonymizer->run('order', $data, 'noop');

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);

echo PHP_EOL . 'Check $data == $anonymizedData' . PHP_EOL;
var_dump($data == $anonymizedData);

echo PHP_EOL . 'Check $data === $anonymizedData' . PHP_EOL;
var_dump($data === $anonymizedData);
