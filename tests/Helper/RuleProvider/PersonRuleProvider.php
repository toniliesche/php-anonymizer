<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\RuleProvider;

use Generator;
use PhpAnonymizer\Anonymizer\RuleProvider\BasicArrayRuleProvider;

readonly class PersonRuleProvider extends BasicArrayRuleProvider
{
    protected function getRules(): Generator
    {
        yield 'person' => [
            [
                'name' => 'person',
                'children' => [
                    ['name' => 'firstName'],
                ],
            ],
        ];
    }
}
