<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Exception\ChildNodeNotFoundException;

class Tree implements ChildNodeAccessInterface
{
    /**
     * @param Node[] $childNodes
     */
    public function __construct(
        public array $childNodes = [],
    ) {
    }

    public function addChildNode(Node $node): void
    {
        $this->childNodes[] = $node;
    }

    public function getChildNode(string $name): Node
    {
        foreach ($this->childNodes as $node) {
            if ($node->name === $name) {
                return $node;
            }
        }

        throw new ChildNodeNotFoundException(\sprintf('Child node with name "%s" not found.', $name));
    }

    public function hasChildNode(string $name): bool
    {
        foreach ($this->childNodes as $node) {
            if ($node->name === $name) {
                return true;
            }
        }

        return false;
    }
}
