<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use PhpAnonymizer\Anonymizer\Examples\Order;
use PhpAnonymizer\Anonymizer\Examples\Person;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
    ],
    defaultDataAccess: DataAccess::AUTODETECT->value,
);

$person = new Person(
    firstName: 'John',
    lastName: 'Doe',
);

$data = new Order(
    $person,
);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'clone',
);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);
echo PHP_EOL;

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
echo PHP_EOL;

echo PHP_EOL . 'Check $data == $anonymizedData' . PHP_EOL;
var_dump($data == $anonymizedData);
echo PHP_EOL;

echo PHP_EOL . 'Check $data === $anonymizedData' . PHP_EOL;
var_dump($data === $anonymizedData);
echo PHP_EOL;
