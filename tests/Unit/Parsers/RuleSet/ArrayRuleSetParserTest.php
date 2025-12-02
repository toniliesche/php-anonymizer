<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\ArrayRuleSetParser;
use PHPUnit\Framework\TestCase;

class ArrayRuleSetParserTest extends TestCase
{
    public function testParseRules(): void
    {
        $parser = new ArrayRuleSetParser(new ArrayNodeParser());
        $tree = $parser->parseDefinition(
            [
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
        );

        $dataLevel = $tree->getChildNode('data');
        $this->assertEquals('data', $dataLevel->name);
        $this->assertEquals('array', $dataLevel->dataAccess);
        $this->assertEquals(NodeType::NODE, $dataLevel->nodeType);
        $this->assertFalse($dataLevel->isArray);

        $addressLevel = $dataLevel->getChildNode('address');
        $this->assertEquals('address', $addressLevel->name);
        $this->assertEquals('property', $addressLevel->dataAccess);
        $this->assertEquals(NodeType::NODE, $addressLevel->nodeType);
        $this->assertTrue($addressLevel->isArray);

        $nameLevel = $addressLevel->getChildNode('name');
        $this->assertEquals('name', $nameLevel->name);
        $this->assertEquals('setter', $nameLevel->dataAccess);
        $this->assertEquals(NodeType::LEAF, $nameLevel->nodeType);
        $this->assertFalse($nameLevel->isArray);
    }

    public function testInvalidNodeNameDetected(): void
    {
        $parser = new ArrayRuleSetParser(new ArrayNodeParser());
        $this->expectException(InvalidNodeNameException::class);
        $parser->parseDefinition(
            [
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
        );
    }

    public function testInvalidNodeDefinitionDetected(): void
    {
        $parser = new ArrayRuleSetParser(new ArrayNodeParser());
        $this->expectException(InvalidNodeDefinitionException::class);
        $parser->parseDefinition(
            [
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
        );
    }
}
