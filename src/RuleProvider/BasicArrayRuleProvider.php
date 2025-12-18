<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\RuleProvider;

use Generator;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Mapper\Node\DefaultNodeMapper;
use PhpAnonymizer\Anonymizer\Model\RuleSet;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\ArrayRuleSetParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface;

abstract readonly class BasicArrayRuleProvider implements RuleProviderInterface
{
    private RuleSetParserInterface $ruleParser;

    public function __construct(
        private DataAccess $defaultDataAccess = DataAccess::ARRAY,
    ) {
        $this->ruleParser = new ArrayRuleSetParser(
            nodeParser: new ArrayNodeParser(),
            nodeMapper: new DefaultNodeMapper(),
        );
    }

    public function provideRules(): Generator
    {
        foreach ($this->getRules() as $ruleName => $definition) {
            yield $ruleName => new RuleSet(
                tree: $this->ruleParser->parseDefinition($definition),
                defaultDataAccess: $this->defaultDataAccess->value,
            );
        }
    }

    /**
     * @return Generator<string, array<mixed>>
     */
    abstract protected function getRules(): Generator;
}
