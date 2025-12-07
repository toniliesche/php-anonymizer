<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

use PhpAnonymizer\Anonymizer\Exception\InvalidRegExpException;
use PhpAnonymizer\Anonymizer\Model\NodeParsingResult;
use Safe\Exceptions\PcreException;
use function is_string;
use function Safe\preg_match;
use function sprintf;

/**
 * @deprecated - part of deprecated RegexpRuleSetParser
 */
abstract class AbstractRegexpParser implements NodeParserInterface
{
    public function __construct(private readonly string $regexp)
    {
        // @codeCoverageIgnoreStart
        try {
            preg_match($this->regexp, '');
        } catch (PcreException $ex) {
            throw new InvalidRegExpException(
                message: sprintf('Invalid regular expression: "%s"', $this->regexp),
                previous: $ex,
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws PcreException
     */
    public function parseNode(array|string $node, string $path): NodeParsingResult
    {
        if (!is_string($node) || preg_match($this->regexp, $node, $matches) === 0) {
            return new NodeParsingResult(false);
        }

        return new NodeParsingResult(
            isValid: true,
            isArray: isset($matches['array']) && ($matches['array'] !== ''),
            property: $matches['property'],
            dataAccess: $this->getValue($matches, 'data_access'),
            valueType: $this->getValue($matches, 'value'),
            nestedType: $this->getValue($matches, 'nested_type'),
            nestedRule: $this->getValue($matches, 'nested_rule'),
            filterField: $this->getValue($matches, 'filter_field'),
            filterValue: $this->getValue($matches, 'filter_value'),
        );
    }

    /**
     * @param null|array<string,string> $matches
     */
    private function getValue(?array $matches, string $field): ?string
    {
        return isset($matches[$field]) && ($matches[$field] !== '') ? $matches[$field] : null;
    }
}
