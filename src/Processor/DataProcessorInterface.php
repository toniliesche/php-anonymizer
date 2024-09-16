<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Processor;

use PhpAnonymizer\Anonymizer\Model\RuleSet;

interface DataProcessorInterface
{
    public function process(mixed $data, RuleSet $ruleSet, ?string $encoding = null): mixed;
}
