<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

interface ChildNodeAccessInterface
{
    public function addChildNode(Node $node): void;

    public function getChildNode(string $name): Node;

    public function hasChildNode(string $name): bool;
}
