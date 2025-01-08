<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Exception\NodeDefinitionMismatchException;
use PhpAnonymizer\Anonymizer\Model\Node;
use PhpAnonymizer\Anonymizer\Model\Tree;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\Node\SimpleRegexParser;
use Safe\Exceptions\PcreException;
use function count;
use function explode;
use function is_string;
use function sprintf;

readonly class DefaultRuleSetParser implements RuleSetParserInterface
{
    public function __construct(
        private NodeParserInterface $nodeParser = new SimpleRegexParser(),
    ) {
    }

    /**
     * @throws PcreException
     */
    public function parseDefinition(array $rules): Tree
    {
        $tree = new Tree();

        foreach ($rules as $definition) {
            if (!is_string($definition)) {
                throw new InvalidArgumentException('Rule definition must be a string.');
            }

            $definitionParts = explode('.', $definition);
            $levels = count($definitionParts);
            $childNode = $tree;

            foreach ($definitionParts as $level => $nodeName) {
                $parentNode = $childNode;
                $ruleResult = $this->nodeParser->parseNodeName($nodeName);
                if (!$ruleResult->isValid) {
                    throw new InvalidNodeNameException(sprintf('Invalid node name "%s".', $nodeName));
                }

                $nodeType = $level > ($levels - 2) ? NodeType::LEAF : NodeType::NODE;
                $dataAccess = $ruleResult->dataAccess ?? DataAccess::DEFAULT->value;

                if ($parentNode->hasChildNode($ruleResult->property)) {
                    $childNode = $parentNode->getChildNode($ruleResult->property);
                    if ($childNode->nodeType !== $nodeType || $childNode->dataAccess !== $dataAccess || $childNode->isArray !== $ruleResult->isArray) {
                        throw new NodeDefinitionMismatchException(
                            sprintf('Node definition mismatch for node "%s".', $ruleResult->property),
                        );
                    }

                    continue;
                }

                $childNode = new Node(
                    name: $ruleResult->property,
                    dataAccess: $dataAccess,
                    nodeType: $nodeType,
                    valueType: $ruleResult->valueType,
                    isArray: $ruleResult->isArray,
                    nestedType: $ruleResult->nestedType,
                    nestedRule: $ruleResult->nestedRule,
                );
                $parentNode->addChildNode($childNode);
            }
        }

        return $tree;
    }
}
