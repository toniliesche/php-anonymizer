<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model\Data;

use PhpAnonymizer\Anonymizer\Enum\NodeType;

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
        public bool $isList,
        array $childNodes = [],
    ) {
        $this->childNodes = $childNodes;
    }

    /**
     * @param string[] $path
     */
    public function mergeChildrenFromNode(Node $node, array $path): void
    {
        foreach ($node->childNodes as $childNode) {
            if (!$this->hasChildNode($childNode->name)) {
                $this->addChildNode($childNode);

                continue;
            }

            $tempPath = $path;
            $tempPath[] = $childNode->name;
            $this->getChildNode($childNode->name)->mergeChildrenFromNode($childNode, $tempPath);
        }
    }
}
