<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\NodeConflictException;

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

            $this->addChildNode($childNode);
        }
    }

    public function addChildNode(Node $node): void
    {
        foreach ($this->childNodes as $childNode) {
            if (!$this->checkChildNodeConflict($node, $childNode)) {
                continue;
            }

            throw new NodeConflictException(
                sprintf(
                    'Node already contains a child node with name "%s".',
                    $node->name,
                ),
            );
        }

        $this->childNodes[] = $node;
    }
}
