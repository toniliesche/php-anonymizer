<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Model\RuleSet;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface;
use PhpAnonymizer\Anonymizer\Processor\DataProcessorInterface;

final readonly class Anonymizer
{
    public function __construct(
        private RuleSetParserInterface $ruleSetParser,
        private DataProcessorInterface $dataProcessor,
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
        return $this->dataProcessor->process($data, $ruleSetName, $encoding);
    }

    /**
     * @param array<mixed> $definitions
     */
    public function registerRuleSet(string $name, array $definitions, string $defaultDataAccess = DataAccess::ARRAY->value): void
    {
        if ($defaultDataAccess === DataAccess::DEFAULT->value) {
            throw new InvalidArgumentException('Default data access for rule set cannot be DEFAULT');
        }

        $tree = $this->ruleSetParser->parseDefinition($definitions);
        $ruleSet = new RuleSet(
            tree: $tree,
            defaultDataAccess: $defaultDataAccess,
        );

        $this->dataProcessor->getRuleSetProvider()->registerRuleSet($name, $ruleSet);
    }
}
