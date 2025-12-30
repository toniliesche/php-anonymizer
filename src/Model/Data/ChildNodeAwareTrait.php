<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model\Data;

use PhpAnonymizer\Anonymizer\Exception\ChildNodeNotFoundException;

trait ChildNodeAwareTrait
{
    /** @var Node[] */
    public array $childNodes;

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

    public function addChildNode(Node $node): void
    {
        $this->childNodes[] = $node;
    }
}
