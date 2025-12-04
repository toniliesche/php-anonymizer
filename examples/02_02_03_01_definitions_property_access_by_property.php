<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Examples\Person;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    // set nodeParserType to complex here for use of advanced syntax
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.person.firstName[property]',
        'order.person.lastName[property]',
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
