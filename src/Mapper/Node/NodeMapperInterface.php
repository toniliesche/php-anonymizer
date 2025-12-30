<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Mapper\Node;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Model\Rule\Node;
use PhpAnonymizer\Anonymizer\Model\Rule\NodeParsingResult;

interface NodeMapperInterface
{
    public function mapNodeParsingResult(NodeParsingResult $ruleResult, NodeType $nodeType, string $defaultDataAccess): Node;
}
