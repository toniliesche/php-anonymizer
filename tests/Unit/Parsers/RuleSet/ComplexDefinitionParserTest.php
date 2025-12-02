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

final class ComplexDefinitionParserTest extends TestCase
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
