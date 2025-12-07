<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory;

use PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface;

interface RuleSetParserFactoryInterface
{
    public function getRuleSetParser(
        ?string $type,
        ?NodeParserInterface $nodeParser = null,
        ?NodeMapperInterface $nodeMapper = null,
    ): ?RuleSetParserInterface;
}
