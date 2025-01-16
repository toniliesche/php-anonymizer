<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/includes/order_with_json_address.php';

use Faker\Factory;
use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;

$faker = Factory::create('de_DE');
$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->withCustomFaker($faker)
    ->withFakerSeed('codeword')
    ->build();

$anonymizer->registerRuleSet(
    'order',
    [
        'order.address[property?json/address]',
    ],
);

$anonymizer->registerRuleSet(
    'address',
    [
        'firstName',
        'lastName',
    ],
);

$data = [
    'order' => new OrderWithJsonAddress(
        '{"firstName":"John","lastName":"Doe"}',
    ),
];

$anonymizedData = $anonymizer->run('order', $data);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
