<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\UnknownRuleSetException;
use PhpAnonymizer\Anonymizer\Model\RuleSet;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface;
use PhpAnonymizer\Anonymizer\Processor\DataProcessorInterface;

class Anonymizer
{
    /** @var RuleSet[] */
    private array $ruleSets = [];

    public function __construct(
        private readonly RuleSetParserInterface $ruleSetParser,
        private readonly DataProcessorInterface $processor,
    ) {
    }

    /**
     * @template T
     *
     * @param T $data
     *
     * @return T
     */
    public function run(string $ruleSetName, mixed $data, ?string $encoding = null): mixed
    {
        $ruleSet = $this->ruleSets[$ruleSetName] ?? throw new UnknownRuleSetException(\sprintf('Rule set "%s" not found', $ruleSetName));

        return $this->processor->process($data, $ruleSet, $encoding);
    }

    /**
     * @param string[] $definitions
     */
    public function registerRuleSet(string $name, array $definitions, string $defaultDataAccess = DataAccess::ARRAY->value): void
    {
        if ($defaultDataAccess === DataAccess::DEFAULT->value) {
            throw new InvalidArgumentException('Default data access cannot be DEFAULT');
        }

        $tree = $this->ruleSetParser->parseDefinition($definitions);
        $this->ruleSets[$name] = new RuleSet(
            tree: $tree,
            defaultDataAccess: $defaultDataAccess,
        );
    }
}
