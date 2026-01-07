<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Processor;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Model\Rule\Node;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSet;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSetProvider;
use PhpAnonymizer\Anonymizer\Model\Rule\Tree;
use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PHPUnit\Framework\TestCase;

final class DefaultDataProcessorTest extends TestCase
{
    public function testProcessesAllowListRuleSet(): void
    {
        $ruleSetProvider = new RuleSetProvider();
        $ruleSetProvider->registerRuleSet('allow', new RuleSet(
            tree: new Tree(),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        ));

        $processor = new DefaultDataProcessor(
            new DefaultDataAccessProvider(),
            new DefaultDataGeneratorProvider([new StarMaskedStringGenerator()]),
            new DefaultDataEncodingProvider(),
            $ruleSetProvider,
        );

        $result = $processor->process(
            [
                'name' => 'John',
                'city' => 'NY',
            ],
            'allow',
        );

        self::assertSame('****', $result['name']);
        self::assertSame('**', $result['city']);
    }

    public function testProcessesDenyListRuleSet(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $ruleSetProvider = new RuleSetProvider();
        $ruleSetProvider->registerRuleSet('deny', new RuleSet(
            tree: new Tree([$nameNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: true,
        ));

        $processor = new DefaultDataProcessor(
            new DefaultDataAccessProvider(),
            new DefaultDataGeneratorProvider([new StarMaskedStringGenerator()]),
            new DefaultDataEncodingProvider(),
            $ruleSetProvider,
        );

        $result = $processor->process(
            [
                'name' => 'John',
                'city' => 'NY',
            ],
            'deny',
        );

        self::assertSame('****', $result['name']);
        self::assertSame('NY', $result['city']);
    }

    public function testReturnsRuleSetProvider(): void
    {
        $ruleSetProvider = new RuleSetProvider();
        $processor = new DefaultDataProcessor(
            new DefaultDataAccessProvider(),
            new DefaultDataGeneratorProvider([new StarMaskedStringGenerator()]),
            new DefaultDataEncodingProvider(),
            $ruleSetProvider,
        );

        self::assertSame($ruleSetProvider, $processor->getRuleSetProvider());
    }
}
