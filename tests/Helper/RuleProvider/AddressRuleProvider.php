<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\RuleProvider;

use Generator;
use PhpAnonymizer\Anonymizer\RuleProvider\BasicArrayRuleProvider;

final readonly class AddressRuleProvider extends BasicArrayRuleProvider
{
    protected function getRules(): Generator
    {
        yield 'address' => [
            [
                'name' => 'address',
                'children' => [
                    ['name' => 'name'],
                ],
            ],
        ];
    }
}
