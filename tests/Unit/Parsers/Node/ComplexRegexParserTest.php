<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\Node;

use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;
use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexpParser;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\PcreException;

final class ComplexRegexParserTest extends TestCase
{
    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeName(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data', '');

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

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccess(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[property]', '');

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

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[#name]', '');

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

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithNestedType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[?json/address]', '');

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

    /**
     * @throws PcreException
     */
    public function testWillFailOnDataTypeAndNestedType(): void
    {
        $parser = new ComplexRegexpParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode('data[#name?json/address]', '');
    }

    /**
     * @throws PcreException
     */
    public function testWillFailOnMissingNestedTypeRule(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[?json]', '');

        self::assertFalse($result->isValid);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessAndDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[property#name]', '');

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertSame('property', $result->dataAccess);
        self::assertSame('name', $result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessAndNestedType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[property?json/address]', '');

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertSame('property', $result->dataAccess);
        self::assertNull($result->valueType);
        self::assertSame('json', $result->nestedType);
        self::assertSame('address', $result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithFilter(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[%name/firstName]', '');

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

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessAndFilter(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[property%name/firstName]', '');

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertSame('property', $result->dataAccess);
        self::assertNull($result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertSame('name', $result->filterField);
        self::assertSame('firstName', $result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessAndDataTypeAndFilter(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[property#firstName%name/firstName]', '');

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertSame('property', $result->dataAccess);
        self::assertSame('firstName', $result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertSame('name', $result->filterField);
        self::assertSame('firstName', $result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessFilterAndDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('data[property%name/firstName#firstName]', '');

        self::assertTrue($result->isValid);
        self::assertFalse($result->isArray);
        self::assertSame('data', $result->property);
        self::assertSame('property', $result->dataAccess);
        self::assertSame('firstName', $result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertSame('name', $result->filterField);
        self::assertSame('firstName', $result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseArrayNodeName(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('[]data', '');

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

    /**
     * @throws PcreException
     */
    public function testCanParseArrayNodeNameWithDataAccess(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('[]data[property]', '');

        self::assertTrue($result->isValid);
        self::assertTrue($result->isArray);
        self::assertSame('data', $result->property);
        self::assertSame('property', $result->dataAccess);
        self::assertNull($result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseArrayNodeNameWithDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('[]data[#name]', '');

        self::assertTrue($result->isValid);
        self::assertTrue($result->isArray);
        self::assertSame('data', $result->property);
        self::assertNull($result->dataAccess);
        self::assertSame('name', $result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseArrayNodeNameWithDataAccessAndDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNode('[]data[property#name]', '');

        self::assertTrue($result->isValid);
        self::assertTrue($result->isArray);
        self::assertSame('data', $result->property);
        self::assertSame('property', $result->dataAccess);
        self::assertSame('name', $result->valueType);
        self::assertNull($result->nestedType);
        self::assertNull($result->nestedRule);
        self::assertNull($result->filterField);
        self::assertNull($result->filterValue);
    }
}
