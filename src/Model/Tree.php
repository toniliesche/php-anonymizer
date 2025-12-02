<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;

final class Tree implements ChildNodeAccessInterface
{
    use ChildNodeAwareTrait;

    /**
     * @param Node[] $childNodes
     */
    public function __construct(
        array $childNodes = [],
    ) {
        foreach ($childNodes as $childNode) {
            if (!$childNode instanceof Node) {
                throw new InvalidArgumentException('All child nodes must be of type Node');
            }
        }

        $this->childNodes = $childNodes;
    }

    public function addChildNode(Node $node): void
    {
        $this->childNodes[] = $node;
    }
}
