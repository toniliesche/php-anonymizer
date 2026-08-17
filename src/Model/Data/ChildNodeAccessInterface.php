<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model\Data;

interface ChildNodeAccessInterface
{
    public function addChildNode(Node $node): void;

    public function getChildNode(string $name): Node;

    public function hasChildNode(string $name): bool;
}
