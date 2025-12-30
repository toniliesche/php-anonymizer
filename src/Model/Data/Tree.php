<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model\Data;

final class Tree implements ChildNodeAccessInterface
{
    use ChildNodeAwareTrait;

    /**
     * @param Node[] $childNodes
     */
    public function __construct(
        array $childNodes = [],
    ) {
        $this->childNodes = $childNodes;
    }
}
