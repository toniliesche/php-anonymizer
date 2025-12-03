<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeParserException;
use PhpAnonymizer\Anonymizer\Exception\MissingNodeDefinitionException;
use PhpAnonymizer\Anonymizer\Mapper\Node\DefaultNodeMapper;
use PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface;
use PhpAnonymizer\Anonymizer\Model\ChildNodeAccessInterface;
use PhpAnonymizer\Anonymizer\Model\Node;
use PhpAnonymizer\Anonymizer\Model\NodeParsingResult;
use PhpAnonymizer\Anonymizer\Model\Tree;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use function array_key_exists;
use function implode;
use function sprintf;

final readonly class ArrayRuleSetParser implements RuleSetParserInterface
{
    public function __construct(
        private NodeParserInterface $nodeParser = new ArrayNodeParser(),
        private NodeMapperInterface $nodeMapper = new DefaultNodeMapper(),
    ) {
        if (!$this->nodeParser instanceof ArrayNodeParser) {
            throw new InvalidNodeParserException(sprintf('Node Parser must be of type %s, %s given', ArrayNodeParser::class, $this->nodeParser::class));
        }
    }

    public function parseDefinition(array $definition): Tree
    {
        $tree = new Tree();

        if (!array_key_exists('nodes', $definition) || !is_array($definition['nodes'])) {
            throw new MissingNodeDefinitionException('Node definition must have at least one node.');
        }

        foreach ($definition['nodes'] as $nodes) {
            $this->parseNode($tree, $nodes, []);
        }

        return $tree;
    }

    /**
     * @param array<mixed> $definition
     * @param string[] $path
     */
    private function parseNode(ChildNodeAccessInterface $parentNode, array $definition, array $path): void
    {
        $ruleResult = $this->nodeParser->parseNode($definition, implode('.', $path));
        $path[] = $ruleResult->property;

        $childNode = $this->getChildNode($parentNode, $definition, $ruleResult);
        if (array_key_exists('children', $definition) && is_array($definition['children'])) {
            foreach ($definition['children'] as $child) {
                $this->parseNode($childNode, $child, $path);
            }
        }
    }

    /**
     * @param array<mixed> $definition
     */
    private function getChildNode(ChildNodeAccessInterface $parentNode, array $definition, NodeParsingResult $ruleResult): Node
    {
        $nodeType = array_key_exists('children', $definition) ? NodeType::NODE : NodeType::LEAF;

        $dataAccess = $ruleResult->dataAccess ?? DataAccess::DEFAULT->value;
        if ($parentNode->hasConflictingChildNode($ruleResult, $dataAccess, $nodeType)) {
            return $parentNode->getChildNode($ruleResult->property);
        }

        $childNode = $this->nodeMapper->mapNodeParsingResult($ruleResult, $nodeType, DataAccess::DEFAULT->value);
        $parentNode->addChildNode($childNode);

        return $childNode;
    }
}
