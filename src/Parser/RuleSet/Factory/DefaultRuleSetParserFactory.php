<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory;

use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use PhpAnonymizer\Anonymizer\Exception\InvalidRuleSetParserDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\RulesetParserExistsException;
use PhpAnonymizer\Anonymizer\Exception\UnknownRuleSetParserException;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface;
use function in_array;
use function is_callable;
use function is_null;
use function sprintf;

final class DefaultRuleSetParserFactory implements RuleSetParserFactoryInterface
{
    private const RULE_SET_PARSERS = [
        RuleSetParser::DEFAULT->value,
    ];

    /** @var array<string,RuleSetParserInterface> */
    private array $ruleSetParsers = [];

    /** @var array<string,callable> */
    private array $customRuleSetParsers = [];

    /**
     * @param callable|RuleSetParserInterface $definition
     */
    public function registerCustomRuleSetParser(string $name, mixed $definition): void
    {
        if (in_array($name, self::RULE_SET_PARSERS, true) || in_array($name, $this->customRuleSetParsers, true)) {
            throw new RulesetParserExistsException(sprintf('Cannot override existing rule set parser: "%s"', $name));
        }

        if ($definition instanceof RuleSetParserInterface) {
            $this->customRuleSetParsers[$name] = static fn () => $definition;

            return;
        }

        if (!is_callable($definition)) {
            throw new InvalidRuleSetParserDefinitionException('Rule set parser definition must be a callable');
        }

        $this->customRuleSetParsers[$name] = $definition;
    }

    public function getRuleSetParser(?string $type, ?NodeParserInterface $nodeParser = null): RuleSetParserInterface
    {
        if (is_null($type)) {
            throw new InvalidRuleSetParserDefinitionException('Rule set parser type must be provided');
        }

        if (!isset($this->ruleSetParsers[$type])) {
            $this->ruleSetParsers[$type] = $this->resolveRuleSetParser($type, $nodeParser);
        }

        return $this->ruleSetParsers[$type];
    }

    private function resolveRuleSetParser(string $type, ?NodeParserInterface $nodeParser): RuleSetParserInterface
    {
        if (isset($this->customRuleSetParsers[$type])) {
            $ruleSetParser = $this->customRuleSetParsers[$type]($nodeParser);
            if (!$ruleSetParser instanceof RuleSetParserInterface) {
                throw new InvalidRuleSetParserDefinitionException('Custom rule set parser must implement RuleSetParserInterface');
            }

            return $ruleSetParser;
        }

        return match ($type) {
            RuleSetParser::DEFAULT->value => new DefaultRuleSetParser($nodeParser),
            default => throw new UnknownRuleSetParserException(sprintf('Unknown rule set parser: "%s"', $type)),
        };
    }
}
