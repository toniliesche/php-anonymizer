<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Faker\Factory;
use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Examples\Person;

$faker = Factory::create('de_DE');
$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->withCustomFaker($faker)
    ->withFakerSeed('codeword')
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.person.firstName[property#firstName]',
        'order.person.lastName[property#lastName]',
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
