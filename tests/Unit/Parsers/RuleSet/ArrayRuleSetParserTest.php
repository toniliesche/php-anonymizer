<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\ArrayRuleSetParser;
use PHPUnit\Framework\TestCase;

final class ArrayRuleSetParserTest extends TestCase
{
    public function testParseRules(): void
    {
        $parser = new ArrayRuleSetParser(new ArrayNodeParser());
        $tree = $parser->parseDefinition(
            [
                'nodes' => [
                    [
                        'name' => 'data',
                        'data_access' => 'array',
                        'children' => [
                            [
                                'name' => 'address',
                                'is_array' => true,
                                'data_access' => 'property',
                                'children' => [
                                    [
                                        'name' => 'name',
                                        'data_access' => 'setter',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $dataLevel = $tree->getChildNode('data');
        self::assertEquals('data', $dataLevel->name);
        self::assertEquals('array', $dataLevel->dataAccess);
        self::assertEquals(NodeType::NODE, $dataLevel->nodeType);
        self::assertFalse($dataLevel->isArray);

        $addressLevel = $dataLevel->getChildNode('address');
        self::assertEquals('address', $addressLevel->name);
        self::assertEquals('property', $addressLevel->dataAccess);
        self::assertEquals(NodeType::NODE, $addressLevel->nodeType);
        self::assertTrue($addressLevel->isArray);

        $nameLevel = $addressLevel->getChildNode('name');
        self::assertEquals('name', $nameLevel->name);
        self::assertEquals('setter', $nameLevel->dataAccess);
        self::assertEquals(NodeType::LEAF, $nameLevel->nodeType);
        self::assertFalse($nameLevel->isArray);
    }

    public function testInvalidNodeNameDetected(): void
    {
        $parser = new ArrayRuleSetParser(new ArrayNodeParser());
        $this->expectException(InvalidNodeNameException::class);
        $parser->parseDefinition(
            [
                'nodes' => [
                    [
                        'name' => 'data',
                        'children' => [
                            [
                                'name' => 'address',
                                'children' => [
                                    [
                                        'name' => 'name!',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
    }

    public function testInvalidNodeDefinitionDetected(): void
    {
        $parser = new ArrayRuleSetParser(new ArrayNodeParser());
        $this->expectException(InvalidNodeDefinitionException::class);
        $parser->parseDefinition(
            [
                'nodes' => [
                    [
                        'name' => 'data',
                        'children' => [
                            [
                                'name' => 'address',
                                'children' => [
                                    [
                                        'name' => 'name',
                                        'value_type' => 'person',
                                        'children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
    }
}
