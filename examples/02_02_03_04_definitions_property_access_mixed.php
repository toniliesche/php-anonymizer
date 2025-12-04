<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Examples\OrderWithSetters;
use PhpAnonymizer\Anonymizer\Examples\Person;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    // set nodeParserType to complex here for use of advanced syntax
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        '[]orders[array].person[setter].firstName[property]',
        '[]orders[array].person[setter].lastName[property]',
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

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
