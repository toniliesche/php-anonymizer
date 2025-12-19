<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\RuleProvider;

use Generator;
use PhpAnonymizer\Anonymizer\Model\RuleSet;

interface RuleProviderInterface
{
    /**
     * @return Generator<string, RuleSet>
     */
    public function provideRules(): Generator;
}
