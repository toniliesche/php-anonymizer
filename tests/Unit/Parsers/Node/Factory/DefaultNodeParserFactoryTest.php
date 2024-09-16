<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\Node\Factory;

use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeParserDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\NodeParserExistsException;
use PhpAnonymizer\Anonymizer\Exception\UnknownNodeParserException;
use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexParser;
use PhpAnonymizer\Anonymizer\Parser\Node\Factory\DefaultNodeParserFactory;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\Node\SimpleRegexParser;
use PHPUnit\Framework\TestCase;
use stdClass;

class DefaultNodeParserFactoryTest extends TestCase
{
    public function testCanProvideNodeParserWithSimpleRegularExpressionsParser(): void
    {
        $factory = new DefaultNodeParserFactory();
        $parser = $factory->getNodeParser(NodeParser::SIMPLE->value);

        $this->assertInstanceOf(SimpleRegexParser::class, $parser);
    }

    public function testCanProvideNodeParserWithComplexRegularExpressionsParser(): void
    {
        $factory = new DefaultNodeParserFactory();
        $parser = $factory->getNodeParser(NodeParser::COMPLEX->value);

        $this->assertInstanceOf(ComplexRegexParser::class, $parser);
    }

    public function testCanProvideNullNodeParser(): void
    {
        $factory = new DefaultNodeParserFactory();
        $parser = $factory->getNodeParser(null);

        $this->assertNull($parser);
    }

    public function testWillFailOnProvideUnknownNodeParser(): void
    {
        $factory = new DefaultNodeParserFactory();
        $this->expectException(UnknownNodeParserException::class);

        $factory->getNodeParser('unknown');
    }

    public function testCanRegisterCustomNodeParserWithCallable(): void
    {
        $callable = fn () => $this->createMock(NodeParserInterface::class);
        $factory = new DefaultNodeParserFactory();
        $factory->registerCustomNodeParser('custom', $callable);

        $parser = $factory->getNodeParser('custom');
        $this->assertInstanceOf(NodeParserInterface::class, $parser);
    }

    public function testCanRegisterCustomNodeParserWithInstance(): void
    {
        $parser = $this->createMock(NodeParserInterface::class);
        $factory = new DefaultNodeParserFactory();
        $factory->registerCustomNodeParser('custom', $parser);

        $resolvedParser = $factory->getNodeParser('custom');
        $this->assertInstanceOf(NodeParserInterface::class, $resolvedParser);
    }

    public function testWillFailOnRegisterCustomNodeParserOnNameConflict(): void
    {
        $callable = fn () => $this->createMock(NodeParserInterface::class);
        $factory = new DefaultNodeParserFactory();

        $this->expectException(NodeParserExistsException::class);
        $factory->registerCustomNodeParser(NodeParser::SIMPLE->value, $callable);
    }

    public function testWillFailOnRegisterCustomNodeParserWithNonCallableDefinition(): void
    {
        $factory = new DefaultNodeParserFactory();

        $this->expectException(InvalidNodeParserDefinitionException::class);

        /** @phpstan-ignore-next-line */
        $factory->registerCustomNodeParser('custom', 'not a callable');
    }

    public function testWillFailOnRegisterCustomNodeParserWhenNodeParserDoesNotImplementInterface(): void
    {
        $callable = fn () => new stdClass();
        $factory = new DefaultNodeParserFactory();

        $this->expectException(InvalidNodeParserDefinitionException::class);
        $factory->registerCustomNodeParser('custom', $callable);
        $factory->getNodeParser('custom');
    }
}
