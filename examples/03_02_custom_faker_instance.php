<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Faker\Factory;
use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;

$faker = Factory::create('de_DE');

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    // set nodeParserType to complex here for use of advanced syntax
    ->withNodeParserType(NodeParser::COMPLEX->value)
    // set faker to true here to use the default faker instance
    ->withCustomFaker($faker)
    ->build();

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.first_name[#firstName]',
        'order.person.last_name[#lastName]',
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

$anonymizedData = $anonymizer->run('order', $data);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
