<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\Node;

use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PHPUnit\Framework\TestCase;

class ArrayNodeParserTest extends TestCase
{
    public function testCanParseSimpleNode(): void
    {
        $parser = new ArrayNodeParser();

        $result = $parser->parseNode(
            [
                'name' => 'data',
            ],
            '',
        );

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertNull($result->dataAccess);
        self::assertNull($result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }

    public function testCanParseSimpleNodeNameWithDataAccess(): void
    {
        $parser = new ArrayNodeParser();

        $result = $parser->parseNode(
            [
                'name' => 'data',
                'data_access' => 'property',
            ],
            '',
        );

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertSame('property', $result->dataAccess);
        self::assertNull($result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }

    public function testCanParseSimpleNodeNameWithDataType(): void
    {
        $parser = new ArrayNodeParser();

        $result = $parser->parseNode(
            [
                'name' => 'data',
                'value_type' => 'name',
            ],
            '',
        );

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertNull($result->dataAccess);
        self::assertSame('name', $result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }

    public function testCanParseSimpleNodeNameWithNestedType(): void
    {
        $parser = new ArrayNodeParser();

        $result = $parser->parseNode(
            [
                'name' => 'data',
                'nested_type' => 'json',
                'nested_rule' => 'address',
            ],
            '',
        );

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertNull($result->dataAccess);
        self::assertNull($result->valueType);
        self::assertSame('json', $result->nestedType);
        self::assertSame('address', $result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }

    public function testWillFailOnDataTypeAndNestedType(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            [
                'name' => 'data',
                'value_type' => 'name',
                'nested_type' => 'json',
                'nested_rule' => 'address',
            ],
            '',
        );
    }

    public function testWillFailOnMissingNestedTypeRule(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            [
                'name' => 'data',
                'nested_type' => 'json',
            ],
            '',
        );
    }

    public function testCanParseSimpleNodeNameWithFilter(): void
    {
        $parser = new ArrayNodeParser();

        $result = $parser->parseNode(
            [
                'name' => 'data',
                'filter_field' => 'name',
                'filter_value' => 'firstName',
            ],
            '',
        );

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertNull($result->dataAccess);
        self::assertNull($result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertSame('name', $result->filterField);
        self::assertSame('firstName', $result->filterValue);
    }

    public function testCanParseArrayNodeName(): void
    {
        $parser = new ArrayNodeParser();

        $result = $parser->parseNode(
            [
                'name' => 'data',
                'is_array' => true,
            ],
            '',
        );

        self::assertTrue($result->isValid);
        self::assertTrue($result->isArray);
        self::assertSame('data', $result->property);
        self::assertNull($result->dataAccess);
        self::assertNull($result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }
}
