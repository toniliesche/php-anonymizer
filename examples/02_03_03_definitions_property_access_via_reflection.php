<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/person_class.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    // set nodeParserType to complex here for use of advanced syntax
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->build();

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.firstName[reflection]',
        'order.person.lastName[reflection]',
    ],
);

$person = new Person('John', 'Doe');

$data = [
    'order' => [
        'person' => $person,
    ],
];

$anonymizedData = $anonymizer->run('order', $data);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
