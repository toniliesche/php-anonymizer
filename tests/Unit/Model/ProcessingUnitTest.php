<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Model;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Model\Node;
use PhpAnonymizer\Anonymizer\Model\ProcessingUnit;
use PhpAnonymizer\Anonymizer\Model\RuleSet;
use PhpAnonymizer\Anonymizer\Model\RuleSetProvider;
use PhpAnonymizer\Anonymizer\Model\Tree;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Address;
use PHPUnit\Framework\TestCase;

class ProcessingUnitTest extends TestCase
{
    public function testCanRunSimpleProcessingOfDataInArray(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $addressNode = new Node(
            name: 'address',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: false,
            childNodes: [
                $nameNode,
            ],
        );

        $tree = new Tree(
            childNodes: [
                $addressNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: $tree,
            defaultDataAccess: DataAccess::ARRAY->value,
        );

        $data = [
            'address' => [
                'name' => 'John Doe',
                'city' => 'New York',
            ],
        ];

        $processingUnit = new ProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            $data,
        );

        $processedData = $processingUnit->process();

        self::assertSame('********', $processedData['address']['name']);
        self::assertSame('New York', $processedData['address']['city']);
    }

    public function testCanRunSimpleProcessingOfDataInListOfArrays(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $addressNode = new Node(
            name: 'addresses',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: true,
            childNodes: [
                $nameNode,
            ],
        );

        $tree = new Tree(
            childNodes: [
                $addressNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: $tree,
            defaultDataAccess: DataAccess::ARRAY->value,
        );

        $data = [
            'addresses' => [
                [
                    'name' => 'John Doe',
                    'city' => 'New York',
                ],
                [
                    'name' => 'Jane Doe',
                    'city' => 'Los Angeles',
                ],
            ],
        ];

        $processingUnit = new ProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            $data,
        );

        $processedData = $processingUnit->process();

        self::assertSame('********', $processedData['addresses'][0]['name']);
        self::assertSame('New York', $processedData['addresses'][0]['city']);
        self::assertSame('********', $processedData['addresses'][1]['name']);
        self::assertSame('Los Angeles', $processedData['addresses'][1]['city']);
    }

    public function testCanRunSimpleDataProcessingOfNonExistantDataInArray(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $addressNode = new Node(
            name: 'address',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: false,
            childNodes: [
                $nameNode,
            ],
        );

        $tree = new Tree(
            childNodes: [
                $addressNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: $tree,
            defaultDataAccess: DataAccess::ARRAY->value,
        );

        $data = [
            'address' => [
                'company' => 'The Testing Corp',
                'city' => 'New York',
            ],
        ];

        $processingUnit = new ProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            $data,
        );

        $processedData = $processingUnit->process();

        self::assertSame('The Testing Corp', $processedData['address']['company']);
        self::assertSame('New York', $processedData['address']['city']);
    }

    public function testCanRunComplexProcessingOfDataWithJsonInput(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::PROPERTY->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $addressNode = new Node(
            name: 'address',
            dataAccess: DataAccess::PROPERTY->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: false,
            childNodes: [
                $nameNode,
            ],
        );

        $tree = new Tree(
            childNodes: [
                $addressNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: $tree,
            defaultDataAccess: DataAccess::ARRAY->value,
        );

        $data = '{"address":{"name":"John Doe","city":"New York"}}';

        $processingUnit = new ProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            $data,
        );

        $processedData = $processingUnit->process('json');
        self::assertSame('{"address":{"name":"********","city":"New York"}}', $processedData);
    }

    public function testCanRunComplexProcessingOfDataWithNestedJsonInput(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::ARRAY->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $addressTree = new Tree(
            childNodes: [
                $nameNode,
            ],
        );

        $addressRuleSet = new RuleSet(
            tree: $addressTree,
            defaultDataAccess: DataAccess::ARRAY->value,
        );

        $addressesNode = new Node(
            name: 'address',
            dataAccess: DataAccess::ARRAY->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
            nestedType: 'json',
            nestedRule: 'address',
        );

        $addressesTree = new Tree(
            childNodes: [
                $addressesNode,
            ],
        );

        $addressesRuleSet = new RuleSet(
            tree: $addressesTree,
            defaultDataAccess: DataAccess::ARRAY->value,
        );

        $ruleSetProvider = new RuleSetProvider();
        $ruleSetProvider->registerRuleSet('address', $addressRuleSet);
        $ruleSetProvider->registerRuleSet('addresses', $addressesRuleSet);

        $data = [
            'address' => '{"name":"John Doe","city":"New York"}',
        ];

        $processingUnit = new ProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            $ruleSetProvider,
            $addressesRuleSet,
            $data,
        );

        $processedData = $processingUnit->process();
        self::assertSame('{"name":"********","city":"New York"}', $processedData['address']);
    }

    public function testWillFailOnArrayProcessingOfSimpleValue(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $addressNode = new Node(
            name: 'addresses',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: true,
            childNodes: [
                $nameNode,
            ],
        );

        $tree = new Tree(
            childNodes: [
                $addressNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: $tree,
            defaultDataAccess: DataAccess::ARRAY->value,
        );

        $data = [
            'addresses' => 'invalid type',
        ];

        $processingUnit = new ProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            $data,
        );

        $this->expectException(InvalidObjectTypeException::class);
        $processingUnit->process();
    }

    public function testWillFailOnProcessingWhenInvalidEncodingIsGiven(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::PROPERTY->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $addressNode = new Node(
            name: 'address',
            dataAccess: DataAccess::PROPERTY->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: false,
            childNodes: [
                $nameNode,
            ],
        );

        $tree = new Tree(
            childNodes: [
                $addressNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: $tree,
            defaultDataAccess: DataAccess::ARRAY->value,
        );

        $data = new Address(
            name: 'John Doe',
            city: 'New York',
        );

        $processingUnit = new ProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            $data,
        );

        $this->expectException(DataEncodingException::class);
        $processingUnit->process('json');
    }
}
