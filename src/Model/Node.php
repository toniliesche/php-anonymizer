<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;

class Node implements ChildNodeAccessInterface
{
    use ChildNodeAwareTrait;

    /**
     * @param Node[] $childNodes
     */
    public function __construct(
        public string $name,
        public string $dataAccess,
        public NodeType $nodeType,
        public ?string $valueType,
        public bool $isArray,
        public ?string $nestedType = null,
        public ?string $nestedRule = null,
        public ?string $filterField = null,
        public ?string $filterValue = null,
        array $childNodes = [],
    ) {
        foreach ($childNodes as $childNode) {
            if (!$childNode instanceof self) {
                throw new InvalidArgumentException('All child nodes must be of type Node');
            }
        }

        $this->childNodes = $childNodes;

        if (!is_null($this->nestedType) && !empty($childNodes)) {
            throw new InvalidArgumentException('Cannot add child nodes to a node that contains a nested type');
        }

        if ($this->nodeType !== NodeType::LEAF) {
            if (!is_null($this->nestedType)) {
                throw new InvalidArgumentException('Cannot define a nested type for a non-leaf node');
            }

            if (!is_null($this->filterField)) {
                throw new InvalidArgumentException('Cannot define a filter field for a non-leaf node');
            }
        }
    }

    public function containsNestedData(): bool
    {
        return !is_null($this->nestedType);
    }

    public function hasFilterRule(): bool
    {
        return !is_null($this->filterField);
    }

    public function addChildNode(Node $node): void
    {
        if (!is_null($this->nestedType)) {
            throw new InvalidArgumentException('Cannot add child nodes to a node that contains a nested type');
        }

        $this->childNodes[] = $node;
    }

    public function definitionMismatch(NodeParsingResult $ruleResult, string $dataAccess, NodeType $nodeType): bool
    {
        return
            $this->nodeType !== $nodeType
            || $this->valueType !== $ruleResult->valueType
            || $this->dataAccess !== $dataAccess
            || $this->isArray !== $ruleResult->isArray
            || $this->nestedType !== $ruleResult->nestedType
            || $this->nestedRule !== $ruleResult->nestedRule;
    }

    public function definitionConflict(NodeParsingResult $ruleResult): bool
    {
        return (bool) ($this->filterField === $ruleResult->filterField && $this->filterValue === $ruleResult->filterValue);
    }

    public static function fromNodeParsingResult(
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
