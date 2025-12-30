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
}
