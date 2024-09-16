<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/person_class.php';
require_once __DIR__ . '/includes/order_with_setters_class.php';

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
        '[]orders[array].person[setter].firstName[property]',
        '[]orders[array].person[setter].lastName[property]',
    ],
);

$person1 = new Person('John', 'Doe');
$order1 = new OrderWithSetters($person1);

$person2 = new Person('John', 'Doe');
$order2 = new OrderWithSetters($person2);

$data = [
    'orders' => [
        $order1,
        $order2,
    ],
];

$anonymizedData = $anonymizer->run('person-data', $data);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
