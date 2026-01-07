<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Model;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Model\Processing\AllowListProcessingUnit;
use PhpAnonymizer\Anonymizer\Model\Rule\Node;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSet;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSetProvider;
use PhpAnonymizer\Anonymizer\Model\Rule\Tree;
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
