<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node\Factory;

use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeParserDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\NodeParserExistsException;
use PhpAnonymizer\Anonymizer\Exception\UnknownNodeParserException;
use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexParser;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\Node\SimpleRegexParser;

class DefaultNodeParserFactory implements NodeParserFactoryInterface
{
    private const NODE_PARSERS = [
        NodeParser::COMPLEX->value,
        NodeParser::SIMPLE->value,
    ];

    /** @var array<string,NodeParserInterface> */
    private array $nodeParsers = [];

    /** @var array<string,callable> */
    private array $customNodeParsers = [];

    /**
     * @param callable|NodeParserInterface $definition
     */
    public function registerCustomNodeParser(string $name, mixed $definition): void
    {
        if (\in_array($name, self::NODE_PARSERS, true) || \in_array($name, $this->customNodeParsers, true)) {
            throw new NodeParserExistsException(\sprintf('Cannot override existing node parser: "%s"', $name));
        }

        if ($definition instanceof NodeParserInterface) {
            $this->customNodeParsers[$name] = static fn () => $definition;

            return;
        }

        if (!\is_callable($definition)) {
            throw new InvalidNodeParserDefinitionException('Node parser definition must either be a callable or an instance of NodeParserInterface');
        }

        $this->customNodeParsers[$name] = $definition;
    }

    public function getNodeParser(?string $type): ?NodeParserInterface
    {
        if ($type === null) {
            return null;
        }

        if (!isset($this->nodeParsers[$type])) {
            $this->nodeParsers[$type] = $this->resolveNodeParser($type);
        }

        return $this->nodeParsers[$type];
    }

    private function resolveNodeParser(string $type): NodeParserInterface
    {
        if (isset($this->customNodeParsers[$type])) {
            $nodeParser = $this->customNodeParsers[$type]();
            if (!$nodeParser instanceof NodeParserInterface) {
                throw new InvalidNodeParserDefinitionException('Custom node parser must implement NodeParserInterface');
            }

            return $nodeParser;
        }

        return match ($type) {
            NodeParser::SIMPLE->value => new SimpleRegexParser(),
            NodeParser::COMPLEX->value => new ComplexRegexParser(),
            default => throw new UnknownNodeParserException(\sprintf('Unknown node parser: "%s"', $type)),
        };
    }
}
