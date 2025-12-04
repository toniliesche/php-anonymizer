<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeParserException;
use PhpAnonymizer\Anonymizer\Mapper\Node\DefaultNodeMapper;
use PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface;
use PhpAnonymizer\Anonymizer\Model\Tree;
use PhpAnonymizer\Anonymizer\Parser\Node\AbstractRegexpParser;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\Node\SimpleRegexpParser;
use Safe\Exceptions\PcreException;
use function count;
use function explode;
use function implode;
use function is_string;
use function sprintf;

class RegexpRuleSetParser implements RuleSetParserInterface
{
    public function __construct(
        private ?NodeParserInterface $nodeParser = new SimpleRegexpParser(),
        private ?NodeMapperInterface $nodeMapper = new DefaultNodeMapper(),
    ) {
        $this->nodeParser ??= new SimpleRegexpParser();
        $this->nodeMapper ??= new DefaultNodeMapper();
        if (!$this->nodeParser instanceof AbstractRegexpParser) {
            throw new InvalidNodeParserException(sprintf('Node Parser must extend %s, %s given', AbstractRegexpParser::class, $this->nodeParser::class));
        }
    }

    /**
     * @throws PcreException
     */
    public function parseDefinition(array $definition): Tree
    {
        $tree = new Tree();

        $path = [];
        foreach ($definition as $definitionRule) {
            if (!is_string($definitionRule)) {
                throw new InvalidArgumentException('Rule definition must be a string.');
            }

            $definitionParts = explode('.', $definitionRule);
            $levels = count($definitionParts);
            $childNode = $tree;

            foreach ($definitionParts as $level => $node) {
                $parentNode = $childNode;
                $ruleResult = $this->nodeParser->parseNode($node, implode('.', $path));
                $path[] = $ruleResult->property;

                if (!$ruleResult->isValid) {
                    throw new InvalidNodeNameException(sprintf('Invalid node name "%s".', $node));
                }

                $nodeType = $level > ($levels - 2) ? NodeType::LEAF : NodeType::NODE;
                $dataAccess = $ruleResult->dataAccess ?? DataAccess::DEFAULT->value;

                if ($parentNode->hasConflictingChildNode($ruleResult, $dataAccess, $nodeType)) {
                    $childNode = $parentNode->getChildNode($ruleResult->property);

                    continue;
                }

                $childNode = $this->nodeMapper->mapNodeParsingResult($ruleResult, $nodeType, $dataAccess);
                $parentNode->addChildNode($childNode);
            }
        }

        return $tree;
    }
}
