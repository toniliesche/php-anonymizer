<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeParserException;
use PhpAnonymizer\Anonymizer\Model\ChildNodeAccessInterface;
use PhpAnonymizer\Anonymizer\Model\NodeMapper;
use PhpAnonymizer\Anonymizer\Model\Tree;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\Node\SimpleRegexpParser;
use function array_key_exists;
use function implode;
use function sprintf;

class ArrayRuleSetParser implements RuleSetParserInterface
{
    public function __construct(
        private readonly NodeParserInterface $nodeParser = new SimpleRegexpParser(),
        private readonly NodeMapper $nodeMapper = new NodeMapper(),
    ) {
        if (!$this->nodeParser instanceof ArrayNodeParser) {
            throw new InvalidNodeParserException(sprintf('Node Parser must be of type %s, %s given', ArrayNodeParser::class, $this->nodeParser::class));
        }
    }

    public function parseDefinition(array $rules): Tree
    {
        $tree = new Tree();

        foreach ($rules as $definition) {
            $this->parseNode($tree, $definition, []);
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

        $nodeType = array_key_exists('children', $definition) ? NodeType::NODE : NodeType::LEAF;
        $childNode = $this->nodeMapper->mapNodeParsingResult($ruleResult, $nodeType, DataAccess::DEFAULT->value);

        $parentNode->addChildNode($childNode);
        if (array_key_exists('children', $definition) && is_array($definition['children'])) {
            foreach ($definition['children'] as $child) {
                $this->parseNode($childNode, $child, $path);
            }
        }
    }
}
