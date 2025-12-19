<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\RuleProvider\PersonRuleProvider;
use PHPUnit\Framework\TestCase;

final class BasicArrayRuleProviderTest extends TestCase
{
    public function testProvidesRuleSetsWithDefaultAccess(): void
    {
        $provider = new PersonRuleProvider();
        $rules = iterator_to_array($provider->provideRules());

        self::assertArrayHasKey('person', $rules);
        $ruleSet = $rules['person'];

        self::assertSame(DataAccess::ARRAY->value, $ruleSet->defaultDataAccess);
        self::assertTrue($ruleSet->tree->hasChildNode('person'));
        self::assertTrue($ruleSet->tree->getChildNode('person')->hasChildNode('firstName'));
    }
}
