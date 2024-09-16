<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/person_with_setters_class.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    // set nodeParserType to complex here for use of advanced syntax
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->build();

$anonymizer->registerRuleSet(
    'person-data',
    [
        'order.person.firstName[setter]',
        'order.person.lastName[setter]',
    ],
);

$person = new PersonWithSetters('John', 'Doe');

$data = [
    'order' => [
        'person' => $person,
    ],
];

$anonymizedData = $anonymizer->run('person-data', $data);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
