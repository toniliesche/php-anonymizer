<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\Node;

use Generator;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArrayNodeParserTest extends TestCase
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

    public function testWillMarkStringDefinitionAsInvalid(): void
    {
        $parser = new ArrayNodeParser();
        $result = $parser->parseNode(
            node: '',
            path: '',
        );

        self::assertFalse($result->isValid);
    }

    public function testWillFailOnMissingName(): void
    {
        $parser = new ArrayNodeParser();
        $this->expectException(InvalidNodeDefinitionException::class);
        $parser->parseNode(
            node: [],
            path: '',
        );
    }

    public function testWillFailOnNonStringName(): void
    {
        $parser = new ArrayNodeParser();
        $this->expectException(InvalidNodeNameException::class);
        $parser->parseNode(
            node: [
                'name' => 123,
            ],
            path: '',
        );
    }

    public function testWillFailOnEmptyStringName(): void
    {
        $parser = new ArrayNodeParser();
        $this->expectException(InvalidNodeNameException::class);
        $parser->parseNode(
            node: [
                'name' => '',
            ],
            path: '',
        );
    }

    public function testWillFailOnMalformedStringName(): void
    {
        $parser = new ArrayNodeParser();
        $this->expectException(InvalidNodeNameException::class);
        $parser->parseNode(
            node: [
                'name' => 'my-node!',
            ],
            path: '',
        );
    }

    #[DataProvider('provideTestOptions')]
    public function testWillVerifyOptions(string $option, string|int $value, bool $valid): void
    {
        $parser = new ArrayNodeParser();

        if (!$valid) {
            $this->expectException(InvalidNodeDefinitionException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $parser->parseNode(
            node: [
                'name' => 'my-node',
                $option => $value,
            ],
            path: '',
        );
    }

    public function testWillFailOnInvalidFilterField(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            node: [
                'name' => 'my-node',
                'filter_value' => 'name',
                'filter_field' => 123,
            ],
            path: '',
        );
    }

    public function testWillFailOnEmptyFilterField(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            node: [
                'name' => 'my-node',
                'filter_value' => 'name',
                'filter_field' => '',
            ],
            path: '',
        );
    }

    public function testWillFailOnMissingFilterField(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            node: [
                'name' => 'my-node',
                'filter_value' => 'name',
            ],
            path: '',
        );
    }

    public function testWillFailOnInvalidFilterValue(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            node: [
                'name' => 'my-node',
                'filter_field' => 'name',
                'filter_value' => 123,
            ],
            path: '',
        );
    }

    public function testWillFailOnEmptyFilterValue(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            node: [
                'name' => 'my-node',
                'filter_field' => 'name',
                'filter_value' => '',
            ],
            path: '',
        );
    }

    public function testWillFailOnMissingFilterValue(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            node: [
                'name' => 'my-node',
                'filter_field' => 'name',
            ],
            path: '',
        );
    }

    public function testWillFailOnInvalidIsArray(): void
    {
        $parser = new ArrayNodeParser();

        $this->expectException(RuleDefinitionException::class);
        $parser->parseNode(
            node: [
                'name' => 'my-node',
                'is_array' => 123,
            ],
            path: '',
        );
    }

    public static function provideTestOptions(): Generator
    {
        foreach (['data_access', 'value_type', 'nested_rule'] as $option) {
            foreach ([123, '', 'default'] as $value) {
                yield [
                    'option' => $option,
                    'value' => $value,
                    'valid' => (is_string($value) && $value !== ''),
                ];
            }
        }
    }
}
