<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Model;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\DataEncoder;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Model\Processing\AllowListProcessingUnit;
use PhpAnonymizer\Anonymizer\Model\Rule\Node;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSet;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSetProvider;
use PhpAnonymizer\Anonymizer\Model\Rule\Tree;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\MissingChildAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\NonArrayListValueAccess;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\NoOpEncodingProvider;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\StubDataAccessProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use function Safe\json_decode;

final class AllowListProcessingUnitTest extends TestCase
{
    use MatchesSnapshots;

    public function testAnonymizesUnlistedLeafValues(): void
    {
        $cityNode = new Node(
            name: 'city',
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
                $cityNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: new Tree([$addressNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'address' => [
                'name' => 'John Doe',
                'city' => 'New York',
            ],
        ];

        $processingUnit = $this->createProcessingUnit($ruleSet, $data);
        $processedData = $processingUnit->process();

        self::assertSame('********', $processedData['address']['name']);
        self::assertSame('New York', $processedData['address']['city']);
        $this->assertMatchesSnapshot($processedData);
    }

    public function testKeepsAllowedListValues(): void
    {
        $tagsNode = new Node(
            name: 'tags',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: true,
        );

        $ruleSet = new RuleSet(
            tree: new Tree([$tagsNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'tags' => ['one', 'two'],
            'name' => 'John Doe',
        ];

        $processingUnit = $this->createProcessingUnit($ruleSet, $data);
        $processedData = $processingUnit->process();

        self::assertSame(['one', 'two'], $processedData['tags']);
        self::assertSame('********', $processedData['name']);
        $this->assertMatchesSnapshot($processedData);
    }

    public function testAnonymizesUnlistedListValues(): void
    {
        $ruleSet = new RuleSet(
            tree: new Tree(),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'tags' => ['one', 'two'],
            'name' => 'John Doe',
        ];

        $processingUnit = $this->createProcessingUnit($ruleSet, $data);
        $processedData = $processingUnit->process();

        self::assertSame(['***', '***'], $processedData['tags']);
        self::assertSame('********', $processedData['name']);
        $this->assertMatchesSnapshot($processedData);
    }

    public function testCanProcessJsonPayload(): void
    {
        $cityNode = new Node(
            name: 'city',
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
                $cityNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: new Tree([$addressNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = '{"address":{"name":"John Doe","city":"New York"}}';

        $processingUnit = new AllowListProcessingUnit(
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
        self::assertSame([
            'address' => [
                'name' => '********',
                'city' => 'New York',
            ],
        ], json_decode((string) $processedData, true, 512, JSON_THROW_ON_ERROR));
        $this->assertMatchesSnapshot($processedData);
    }

    public function testProcessThrowsWhenEncoderDoesNotSupportData(): void
    {
        $ruleSet = new RuleSet(
            tree: new Tree(),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $processingUnit = new AllowListProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            ['name' => 'John Doe'],
        );

        $this->expectException(DataEncodingException::class);
        $processingUnit->process(DataEncoder::JSON->value);
    }

    public function testKeepsLeafValueWhenFilterMatches(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
            filterField: 'status',
            filterValue: 'active',
        );

        $ruleSet = new RuleSet(
            tree: new Tree([$nameNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'name' => 'John Doe',
            'status' => 'active',
        ];

        $processingUnit = $this->createProcessingUnit($ruleSet, $data);
        $processedData = $processingUnit->process();

        self::assertSame('John Doe', $processedData['name']);
        self::assertSame('******', $processedData['status']);
    }

    public function testAnonymizesLeafValueWhenFilterDoesNotMatch(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
            filterField: 'status',
            filterValue: 'active',
        );

        $ruleSet = new RuleSet(
            tree: new Tree([$nameNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'name' => 'John Doe',
            'status' => 'inactive',
        ];

        $processingUnit = $this->createProcessingUnit($ruleSet, $data);
        $processedData = $processingUnit->process();

        self::assertSame('********', $processedData['name']);
        self::assertSame('********', $processedData['status']);
    }

    public function testAnonymizesLeafValueWhenFilterFieldMissing(): void
    {
        $nameNode = new Node(
            name: 'name',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
            filterField: 'status',
            filterValue: 'active',
        );

        $ruleSet = new RuleSet(
            tree: new Tree([$nameNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'name' => 'John Doe',
        ];

        $processingUnit = $this->createProcessingUnit($ruleSet, $data);
        $processedData = $processingUnit->process();

        self::assertSame('********', $processedData['name']);
    }

    public function testProcessesListNodeOfObjects(): void
    {
        $cityNode = new Node(
            name: 'city',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $itemsNode = new Node(
            name: 'items',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::NODE,
            valueType: null,
            isArray: true,
            childNodes: [
                $cityNode,
            ],
        );

        $ruleSet = new RuleSet(
            tree: new Tree([$itemsNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'items' => [
                ['name' => 'John', 'city' => 'NY'],
                ['name' => 'Jane', 'city' => 'LA'],
            ],
        ];

        $processingUnit = $this->createProcessingUnit($ruleSet, $data);
        $processedData = $processingUnit->process();

        self::assertSame('****', $processedData['items'][0]['name']);
        self::assertSame('NY', $processedData['items'][0]['city']);
        self::assertSame('****', $processedData['items'][1]['name']);
        self::assertSame('LA', $processedData['items'][1]['city']);
    }

    public function testListLeafSkipsNonStringItems(): void
    {
        $ruleSet = new RuleSet(
            tree: new Tree(),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'tags' => ['one', 2],
        ];

        $processingUnit = $this->createProcessingUnit($ruleSet, $data);
        $processedData = $processingUnit->process();

        self::assertSame(['***', 2], $processedData['tags']);
    }

    public function testProcessesNestedRuleSets(): void
    {
        $nestedCityNode = new Node(
            name: 'city',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
        );

        $nestedRuleSet = new RuleSet(
            tree: new Tree([$nestedCityNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $ruleSetProvider = new RuleSetProvider();
        $ruleSetProvider->registerRuleSet('profile', $nestedRuleSet);

        $profileNode = new Node(
            name: 'profile',
            dataAccess: DataAccess::DEFAULT->value,
            nodeType: NodeType::LEAF,
            valueType: null,
            isArray: false,
            nestedType: DataEncoder::JSON->value,
            nestedRule: 'profile',
        );

        $ruleSet = new RuleSet(
            tree: new Tree([$profileNode]),
            defaultDataAccess: DataAccess::ARRAY->value,
            denyList: false,
        );

        $data = [
            'profile' => '{"name":"John","city":"NY"}',
        ];

        $processingUnit = new AllowListProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new DefaultDataAccessProvider(),
            new DefaultDataEncodingProvider(),
            $ruleSetProvider,
            $ruleSet,
            $data,
        );

        $processedData = $processingUnit->process();
        $profileData = json_decode((string) $processedData['profile'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('****', $profileData['name']);
        self::assertSame('NY', $profileData['city']);
    }

    public function testThrowsWhenListNodeValueIsNotArray(): void
    {
        $ruleSet = new RuleSet(
            tree: new Tree(),
            defaultDataAccess: 'custom',
            denyList: false,
        );

        $processingUnit = new AllowListProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new StubDataAccessProvider(new NonArrayListValueAccess()),
            new NoOpEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            ['items' => 'oops'],
        );

        $this->expectException(InvalidObjectTypeException::class);
        $processingUnit->process();
    }

    public function testSkipsNodeWhenChildIsMissing(): void
    {
        $ruleSet = new RuleSet(
            tree: new Tree(),
            defaultDataAccess: 'custom',
            denyList: false,
        );

        $processingUnit = new AllowListProcessingUnit(
            new DefaultDataGeneratorProvider(
                [
                    new StarMaskedStringGenerator(),
                ],
            ),
            new StubDataAccessProvider(new MissingChildAccess()),
            new NoOpEncodingProvider(),
            new RuleSetProvider(),
            $ruleSet,
            ['name' => 'John'],
        );

        self::assertSame(['name' => 'John'], $processingUnit->process());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createProcessingUnit(RuleSet $ruleSet, array $data): AllowListProcessingUnit
    {
        return new AllowListProcessingUnit(
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
    }
}
