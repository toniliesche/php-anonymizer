<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeDefinitionException;
use function sprintf;

final class Node implements ChildNodeAccessInterface
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
                throw new InvalidNodeDefinitionException('All child nodes must be of type Node');
            }

            $this->addChildNode($childNode);
        }

        if ($this->nodeType !== NodeType::LEAF) {
            if (!is_null($this->nestedRule)) {
                throw new InvalidNodeDefinitionException('Cannot define a nested rule for a non-leaf node');
            }

            if (!is_null($this->filterField)) {
                throw new InvalidNodeDefinitionException('Cannot define a filter field for a non-leaf node');
            }

            if (!is_null($this->filterValue)) {
                throw new InvalidNodeDefinitionException('Cannot define a filter value for a non-leaf node');
            }
        }

        if (is_null($this->filterField) xor is_null($this->filterValue)) {
            throw new InvalidNodeDefinitionException('Filter value must not be null if filter field is not null or vice-versa');
        }
    }

    public function containsNestedData(): bool
    {
        return !is_null($this->nestedRule);
    }

    public function hasFilterRule(): bool
    {
        return !is_null($this->filterField);
    }

    public function addChildNode(Node $node): void
    {
        if (!is_null($this->nestedRule)) {
            throw new InvalidNodeDefinitionException('Cannot add child nodes to a node that contains a nested rule');
        }

        foreach ($this->childNodes as $childNode) {
            if (!$this->checkChildNodeConflict($node, $childNode)) {
                continue;
            }

            throw new InvalidNodeDefinitionException(
                sprintf(
                    'Node already contains a child node with name "%s".',
                    $node->name,
                ),
            );
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
        return $this->filterField === $ruleResult->filterField && $this->filterValue === $ruleResult->filterValue;
    }

    public function isFilteredNode(): bool
    {
        return $this->filterField !== null && $this->filterValue !== null;
    }
}
