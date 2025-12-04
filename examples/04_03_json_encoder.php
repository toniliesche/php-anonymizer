<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'first_name',
                        ],
                        [
                            'name' => 'last_name',
                        ],
                    ],
                ],
            ],
        ],
    ],
);

$data = json_encode([
    'order' => [
        'person' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
    ],
], JSON_THROW_ON_ERROR);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'json',
);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);
echo PHP_EOL;

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
echo PHP_EOL;
