<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory;

use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface;

interface RuleSetParserFactoryInterface
{
    public function getRulesetParser(?string $type, ?NodeParserInterface $nodeParser = null): ?RuleSetParserInterface;
}
