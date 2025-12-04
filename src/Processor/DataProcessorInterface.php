<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Processor;

use PhpAnonymizer\Anonymizer\Model\RuleSetProviderInterface;

interface DataProcessorInterface
{
    public function process(mixed $data, string $ruleSetName, ?string $encoding = null): mixed;

    public function getRuleSetProvider(): RuleSetProviderInterface;
}
