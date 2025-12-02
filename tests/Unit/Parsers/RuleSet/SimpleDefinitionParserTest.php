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
        $this->assertSame(DataAccess::DEFAULT->value, $dataLevel->dataAccess);
        $this->assertSame('data', $dataLevel->name);
        $this->assertSame(NodeType::NODE, $dataLevel->nodeType);
        $this->assertCount(1, $dataLevel->childNodes);
        $this->assertFalse($dataLevel->isArray);

        $addressLevel = $dataLevel->getChildNode('address');
        $this->assertSame(DataAccess::DEFAULT->value, $addressLevel->dataAccess);
        $this->assertSame('address', $addressLevel->name);
        $this->assertSame(NodeType::NODE, $addressLevel->nodeType);
        $this->assertCount(1, $addressLevel->childNodes);
        $this->assertFalse($dataLevel->isArray);

        $nameLevel = $addressLevel->getChildNode('name');
        $this->assertSame(DataAccess::DEFAULT->value, $nameLevel->dataAccess);
        $this->assertSame('name', $nameLevel->name);
        $this->assertSame(NodeType::LEAF, $nameLevel->nodeType);
        $this->assertCount(0, $nameLevel->childNodes);
        $this->assertFalse($dataLevel->isArray);
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
        $this->assertSame(DataAccess::DEFAULT->value, $addressLevel->dataAccess);
        $this->assertSame('address', $addressLevel->name);
        $this->assertSame(NodeType::NODE, $addressLevel->nodeType);
        $this->assertCount(2, $addressLevel->childNodes);
        $this->assertTrue($addressLevel->isArray);

        $nameLevel = $addressLevel->getChildNode('name');
        $this->assertSame(DataAccess::DEFAULT->value, $nameLevel->dataAccess);
        $this->assertSame('name', $nameLevel->name);
        $this->assertSame(NodeType::LEAF, $nameLevel->nodeType);
        $this->assertCount(0, $nameLevel->childNodes);
        $this->assertFalse($nameLevel->isArray);

        $streetLevel = $addressLevel->getChildNode('street');
        $this->assertSame(DataAccess::DEFAULT->value, $streetLevel->dataAccess);
        $this->assertSame('street', $streetLevel->name);
        $this->assertSame(NodeType::LEAF, $streetLevel->nodeType);
        $this->assertCount(0, $streetLevel->childNodes);
        $this->assertFalse($streetLevel->isArray);
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
