<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Enum\NodeType;

final class NodeMapper
{
    public function mapNodeParsingResult(
        NodeParsingResult $ruleResult,
        NodeType $nodeType,
        string $defaultDataAccess,
    ): Node {
        return new Node(
            name: $ruleResult->property,
            dataAccess: $ruleResult->dataAccess ?? $defaultDataAccess,
            nodeType: $nodeType,
            valueType: $ruleResult->valueType,
            isArray: $ruleResult->isArray,
        );
    }
}
