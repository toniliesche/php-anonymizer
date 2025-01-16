<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\ChildNodeNotFoundException;
use PhpAnonymizer\Anonymizer\Exception\NodeDefinitionMismatchException;

trait ChildNodeAwareTrait
{
    /** @var Node[] */
    public array $childNodes = [];

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

    public function hasConflictingChildNode(NodeParsingResult $ruleResult, string $dataAccess, NodeType $nodeType): bool
    {
        $conflict = false;

        foreach ($this->childNodes as $childNode) {
            if ($childNode->name !== $ruleResult->property) {
                continue;
            }

            if ($childNode->definitionMismatch($ruleResult, $dataAccess, $nodeType)) {
                throw new NodeDefinitionMismatchException(
                    sprintf('Node definition mismatch for node "%s".', $ruleResult->property),
                );
            }

            if ($childNode->definitionConflict($ruleResult)) {
                $conflict = true;
            }
        }

        return $conflict;
    }
}
