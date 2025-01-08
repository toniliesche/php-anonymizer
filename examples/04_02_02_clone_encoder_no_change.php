<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/person_class.php';
require_once __DIR__ . '/includes/order_class.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->build();

$anonymizer->registerRuleSet(
    'order',
    [
    ],
    DataAccess::AUTODETECT->value,
);

$person = new Person(
    'John',
    'Doe'
);

$data = new Order(
    $person,
);

$anonymizedData = $anonymizer->run('order', $data, 'clone');

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);

echo PHP_EOL . 'Check $data == $anonymizedData' . PHP_EOL;
var_dump($data == $anonymizedData);

echo PHP_EOL . 'Check $data === $anonymizedData' . PHP_EOL;
var_dump($data === $anonymizedData);
