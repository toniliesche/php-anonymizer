<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Mapper\Node;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Model\Node;
use PhpAnonymizer\Anonymizer\Model\NodeParsingResult;

final class DefaultNodeMapper implements NodeMapperInterface
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
            nestedType: $ruleResult->nestedType,
            nestedRule: $ruleResult->nestedRule,
            filterField: $ruleResult->filterField,
            filterValue: $ruleResult->filterValue,
        );
    }
}
