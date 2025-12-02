<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\RuleSet;

use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Exception\NodeDefinitionMismatchException;
use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexpParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;
use PHPUnit\Framework\TestCase;
use stdClass;

class ComplexDefinitionParserTest extends TestCase
{
    public function testCanParseMultiLevelTreeDefinition(): void
    {
        $parser = new DefaultRuleSetParser(new ComplexRegexpParser());
        $tree = $parser->parseDefinition(
            [
                'data[array].[]address[property].name[setter]',
            ],
        );

        $dataLevel = $tree->getChildNode('data');
        $this->assertSame('data', $dataLevel->name);
        $this->assertSame('array', $dataLevel->dataAccess);
        $this->assertSame(NodeType::NODE, $dataLevel->nodeType);
        $this->assertFalse($dataLevel->isArray);

        $addressLevel = $dataLevel->getChildNode('address');
        $this->assertSame('address', $addressLevel->name);
        $this->assertSame('property', $addressLevel->dataAccess);
        $this->assertSame(NodeType::NODE, $addressLevel->nodeType);
        $this->assertTrue($addressLevel->isArray);

        $nameLevel = $addressLevel->getChildNode('name');
        $this->assertSame('name', $nameLevel->name);
        $this->assertSame('setter', $nameLevel->dataAccess);
        $this->assertSame(NodeType::LEAF, $nameLevel->nodeType);
        $this->assertFalse($nameLevel->isArray);
    }

    public function testWillFailOnParseDefinitionWithInvalidDefinitionType(): void
    {
        $parser = new DefaultRuleSetParser(new ComplexRegexpParser());
        $this->expectException(InvalidArgumentException::class);
        $parser->parseDefinition(
            [
                new stdClass(),
            ],
        );
    }

    public function testWillFailOnParseDefinitionWithInvalidNodeName(): void
    {
        $parser = new DefaultRuleSetParser(new ComplexRegexpParser());
        $this->expectException(InvalidNodeNameException::class);
        $parser->parseDefinition(
            [
                'data.address.name!',
            ],
        );
    }

    public function testWillFailOnParseDefinitionWithPrematureRuleEnding(): void
    {
        $parser = new DefaultRuleSetParser(new ComplexRegexpParser());
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
        $parser = new DefaultRuleSetParser(new ComplexRegexpParser());
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
        $parser = new DefaultRuleSetParser(new ComplexRegexpParser());
        $this->expectException(NodeDefinitionMismatchException::class);
        $parser->parseDefinition(
            [
                'data.[]address',
                'data.address',
            ],
        );
    }
}
