<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\Node;

use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;
use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexpParser;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\PcreException;

class ComplexRegexParserTest extends TestCase
{
    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeName(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertNull($result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccess(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[property]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertNull($result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[#name]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertSame('name', $result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithNestedType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[?json/address]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertNull($result->valueType);
        $this->assertSame('json', $result->nestedType);
        $this->assertSame('address', $result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testWillFailOnDataTypeAndNestedType(): void
    {
        $parser = new ComplexRegexpParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNodeName('data[#name?json/address]', '');
    }

    /**
     * @throws PcreException
     */
    public function testWillFailOnMissingNestedTypeRule(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[?json]', '');

        $this->assertFalse($result->isValid);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessAndDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[property#name]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertSame('name', $result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessAndNestedType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[property?json/address]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertNull($result->valueType);
        $this->assertSame('json', $result->nestedType);
        $this->assertSame('address', $result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithFilter(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[%name/firstName]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertNull($result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertSame('name', $result->filterField);
        $this->assertSame('firstName', $result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessAndFilter(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[property%name/firstName]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertNull($result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertSame('name', $result->filterField);
        $this->assertSame('firstName', $result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessAndDataTypeAndFilter(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[property#firstName%name/firstName]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertSame('firstName', $result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertSame('name', $result->filterField);
        $this->assertSame('firstName', $result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseSimpleNodeNameWithDataAccessFilterAndDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('data[property%name/firstName#firstName]', '');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertSame('firstName', $result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertSame('name', $result->filterField);
        $this->assertSame('firstName', $result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseArrayNodeName(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('[]data', '');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertNull($result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseArrayNodeNameWithDataAccess(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('[]data[property]', '');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertNull($result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseArrayNodeNameWithDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('[]data[#name]', '');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertSame('name', $result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }

    /**
     * @throws PcreException
     */
    public function testCanParseArrayNodeNameWithDataAccessAndDataType(): void
    {
        $parser = new ComplexRegexpParser();

        $result = $parser->parseNodeName('[]data[property#name]', '');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertSame('name', $result->valueType);
        $this->assertNull($result->nestedType);
        $this->assertNull($result->nestedRule);
        $this->assertNull($result->filterField);
        $this->assertNull($result->filterValue);
    }
}
