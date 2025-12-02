<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Exception\NodeDefinitionMismatchException;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;
use PHPUnit\Framework\TestCase;

class SimpleDefinitionParserTest extends TestCase
{
    public function testCanParseMultiLevelTreeDefinition(): void
    {
        $parser = new DefaultRuleSetParser();
        $tree = $parser->parseDefinition(
            [
                'data.address.name',
            ],
        );

        $dataLevel = $tree->getChildNode('data');
        self::assertSame(DataAccess::DEFAULT->value, $dataLevel->dataAccess);
        self::assertSame('data', $dataLevel->name);
        self::assertSame(NodeType::NODE, $dataLevel->nodeType);
        self::assertCount(1, $dataLevel->childNodes);
        self::assertFalse($dataLevel->isArray);

        $addressLevel = $dataLevel->getChildNode('address');
        self::assertSame(DataAccess::DEFAULT->value, $addressLevel->dataAccess);
        self::assertSame('address', $addressLevel->name);
        self::assertSame(NodeType::NODE, $addressLevel->nodeType);
        self::assertCount(1, $addressLevel->childNodes);
        self::assertFalse($dataLevel->isArray);

        $nameLevel = $addressLevel->getChildNode('name');
        self::assertSame(DataAccess::DEFAULT->value, $nameLevel->dataAccess);
        self::assertSame('name', $nameLevel->name);
        self::assertSame(NodeType::LEAF, $nameLevel->nodeType);
        self::assertCount(0, $nameLevel->childNodes);
        self::assertFalse($dataLevel->isArray);
    }

    public function testCanParseMultipleChildNodesDefinition(): void
    {
        $parser = new DefaultRuleSetParser();
        $tree = $parser->parseDefinition(
            [
                '[]address.name',
                '[]address.street',
            ],
        );

        $addressLevel = $tree->getChildNode('address');
        self::assertSame(DataAccess::DEFAULT->value, $addressLevel->dataAccess);
        self::assertSame('address', $addressLevel->name);
        self::assertSame(NodeType::NODE, $addressLevel->nodeType);
        self::assertCount(2, $addressLevel->childNodes);
        self::assertTrue($addressLevel->isArray);

        $nameLevel = $addressLevel->getChildNode('name');
        self::assertSame(DataAccess::DEFAULT->value, $nameLevel->dataAccess);
        self::assertSame('name', $nameLevel->name);
        self::assertSame(NodeType::LEAF, $nameLevel->nodeType);
        self::assertCount(0, $nameLevel->childNodes);
        self::assertFalse($nameLevel->isArray);

        $streetLevel = $addressLevel->getChildNode('street');
        self::assertSame(DataAccess::DEFAULT->value, $streetLevel->dataAccess);
        self::assertSame('street', $streetLevel->name);
        self::assertSame(NodeType::LEAF, $streetLevel->nodeType);
        self::assertCount(0, $streetLevel->childNodes);
        self::assertFalse($streetLevel->isArray);
    }

    public function testWillFailOnParseDefinitionWithInvalidNodeName(): void
    {
        $parser = new DefaultRuleSetParser();
        $this->expectException(InvalidNodeNameException::class);
        $parser->parseDefinition(
            [
                'data.address.name!',
            ],
        );
    }

    public function testWillFailOnParseDefinitionWithPrematureRuleEnding(): void
    {
        $parser = new DefaultRuleSetParser();
        $this->expectException(NodeDefinitionMismatchException::class);
        $parser->parseDefinition(
            [
                'data.address.name',
                'data.address',
            ],
        );
    }

    public function testWillFailOnParseDefinitionWithPrematureRuleEndingScenario2(): void
    {
        $parser = new DefaultRuleSetParser();
        $this->expectException(NodeDefinitionMismatchException::class);
        $parser->parseDefinition(
            [
                'data.address',
                'data.address.name',
            ],
        );
    }

    public function testWillFailOnParseDefinitionWithDefinitionMismatch(): void
    {
        $parser = new DefaultRuleSetParser();
        $this->expectException(NodeDefinitionMismatchException::class);
        $parser->parseDefinition(
            [
                'data.[]address',
                'data.address',
            ],
        );
    }
}
