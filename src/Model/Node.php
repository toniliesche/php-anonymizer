<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\ChildNodeNotFoundException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use function sprintf;

class Node implements ChildNodeAccessInterface
{
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
        public array $childNodes = [],
    ) {
        foreach ($this->childNodes as $childNode) {
            if (!$childNode instanceof self) {
                throw new InvalidArgumentException('All child nodes must be of type Node');
            }
        }

        if (!is_null($this->nestedType) && !empty($childNodes)) {
            throw new InvalidArgumentException('Cannot add child nodes to a node that contains a nested type');
        }
    }

    public function addChildNode(Node $node): void
    {
        if (!is_null($this->nestedType)) {
            throw new InvalidArgumentException('Cannot add child nodes to a node that contains a nested type');
        }

        $this->childNodes[] = $node;
    }

    public function getChildNode(string $name): Node
    {
        foreach ($this->childNodes as $node) {
            if ($node->name === $name) {
                return $node;
            }
        }

        throw new ChildNodeNotFoundException(sprintf('Child node with name "%s" not found.', $name));
    }

    public function hasChildNode(string $name): bool
    {
        foreach ($this->childNodes as $child) {
            if ($child->name === $name) {
                return true;
            }
        }

        return false;
    }

    public function containsNestedData(): bool
    {
        return !is_null($this->nestedType);
    }
}
