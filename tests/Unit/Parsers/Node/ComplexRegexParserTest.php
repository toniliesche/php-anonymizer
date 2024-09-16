<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\Node;

use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexParser;
use PHPUnit\Framework\TestCase;

class ComplexRegexParserTest extends TestCase
{
    public function testCanParseSimpleNodeName(): void
    {
        $parser = new ComplexRegexParser();

        $result = $parser->parseNodeName('data');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertNull($result->valueType);
    }

    public function testCanParseSimpleNodeNameWithDataAccess(): void
    {
        $parser = new ComplexRegexParser();

        $result = $parser->parseNodeName('data[property]');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertNull($result->valueType);
    }

    public function testCanParseSimpleNodeNameWithDataType(): void
    {
        $parser = new ComplexRegexParser();

        $result = $parser->parseNodeName('data[#name]');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertSame('name', $result->valueType);
    }

    public function testCanParseSimpleNodeNameWithDataAccessAndDataType(): void
    {
        $parser = new ComplexRegexParser();

        $result = $parser->parseNodeName('data[property#name]');

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertSame('name', $result->valueType);
    }

    public function testCanParseArrayNodeName(): void
    {
        $parser = new ComplexRegexParser();

        $result = $parser->parseNodeName('[]data');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertNull($result->valueType);
    }

    public function testCanParseArrayNodeNameWithDataAccess(): void
    {
        $parser = new ComplexRegexParser();

        $result = $parser->parseNodeName('[]data[property]');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertNull($result->valueType);
    }

    public function testCanParseArrayNodeNameWithDataType(): void
    {
        $parser = new ComplexRegexParser();

        $result = $parser->parseNodeName('[]data[#name]');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertNull($result->dataAccess);
        $this->assertSame('name', $result->valueType);
    }

    public function testCanParseArrayNodeNameWithDataAccessAndDataType(): void
    {
        $parser = new ComplexRegexParser();

        $result = $parser->parseNodeName('[]data[property#name]');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isArray);
        $this->assertSame('data', $result->property);
        $this->assertSame('property', $result->dataAccess);
        $this->assertSame('name', $result->valueType);
    }
}
