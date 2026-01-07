<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeParserException;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\Node\SimpleRegexpParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\ArrayRuleSetParser;
use PHPUnit\Framework\TestCase;

final class ArrayRuleSetParserTest extends TestCase
{
    public function testCreateWillFailOnInvalidNodeParser(): void
    {
        $this->expectException(InvalidNodeParserException::class);
        new ArrayRuleSetParser(new SimpleRegexpParser());
    }

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
        self::assertSame('data', $dataLevel->name);
        self::assertSame('array', $dataLevel->dataAccess);
        self::assertSame(NodeType::NODE, $dataLevel->nodeType);
        self::assertFalse($dataLevel->isArray);

        $addressLevel = $dataLevel->getChildNode('address');
        self::assertSame('address', $addressLevel->name);
        self::assertSame('property', $addressLevel->dataAccess);
        self::assertSame(NodeType::NODE, $addressLevel->nodeType);
        self::assertTrue($addressLevel->isArray);

        $nameLevel = $addressLevel->getChildNode('name');
        self::assertSame('name', $nameLevel->name);
        self::assertSame('setter', $nameLevel->dataAccess);
        self::assertSame(NodeType::LEAF, $nameLevel->nodeType);
        self::assertFalse($nameLevel->isArray);
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
