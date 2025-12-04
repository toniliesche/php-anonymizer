<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Parsers\RuleSet\Factory;

use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use PhpAnonymizer\Anonymizer\Exception\InvalidRuleSetParserDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\RulesetParserExistsException;
use PhpAnonymizer\Anonymizer\Exception\UnknownRuleSetParserException;
use PhpAnonymizer\Anonymizer\Parser\Node\SimpleRegexpParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory\DefaultRuleSetParserFactory;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DefaultRuleSetParserFactoryTest extends TestCase
{
    public function testCanProvideDefaultRuleSetParser(): void
    {
        $factory = new DefaultRuleSetParserFactory();
        $parser = $factory->getRuleSetParser(RuleSetParser::DEFAULT->value, new SimpleRegexpParser());

        self::assertInstanceOf(DefaultRuleSetParser::class, $parser);
    }

    public function testWillFailOnRetrieveNullRuleSetParser(): void
    {
        $factory = new DefaultRuleSetParserFactory();

        $this->expectException(InvalidRuleSetParserDefinitionException::class);
        $factory->getRuleSetParser(null);
    }

    public function testWillFailOnRetrieveUnknownRuleSetParser(): void
    {
        $factory = new DefaultRuleSetParserFactory();

        $this->expectException(UnknownRuleSetParserException::class);
        $factory->getRuleSetParser('unknown');
    }

    public function testCanRegisterAndRetrieveCustomRuleSetParserWithCallable(): void
    {
        $callable = fn () => $this->createMock(DefaultRuleSetParser::class);
        $factory = new DefaultRuleSetParserFactory();
        $factory->registerCustomRuleSetParser('custom', $callable);

        $parser = $factory->getRuleSetParser('custom');
        self::assertInstanceOf(DefaultRuleSetParser::class, $parser);
    }

    public function testCanRegisterAndRetrieveCustomRuleSetParserWithInstance(): void
    {
        $parser = $this->createMock(DefaultRuleSetParser::class);
        $factory = new DefaultRuleSetParserFactory();
        $factory->registerCustomRuleSetParser('custom', $parser);

        $resolvedParser = $factory->getRuleSetParser('custom');
        self::assertInstanceOf(DefaultRuleSetParser::class, $resolvedParser);
    }

    public function testWillFailOnRegisterCustomRuleSetParserOnNameConflict(): void
    {
        $callable = fn () => $this->createMock(DefaultRuleSetParser::class);
        $factory = new DefaultRuleSetParserFactory();

        $this->expectException(RulesetParserExistsException::class);
        $factory->registerCustomRuleSetParser(RuleSetParser::DEFAULT->value, $callable);
    }

    public function testWillFailOnRegisterCustomRuleSetParserWithNonCallableDefinition(): void
    {
        $factory = new DefaultRuleSetParserFactory();

        $this->expectException(InvalidRuleSetParserDefinitionException::class);

        /** @phpstan-ignore-next-line */
        $factory->registerCustomRuleSetParser('custom', 'invalid');
    }

    public function testWillFailOnRegisterCustomRuleSetParserWhenNotImplementingInterface(): void
    {
        $callable = static fn () => new stdClass();
        $factory = new DefaultRuleSetParserFactory();

        $this->expectException(InvalidRuleSetParserDefinitionException::class);
        $factory->registerCustomRuleSetParser('custom', $callable);
        $factory->getRuleSetParser('custom');
    }
}
