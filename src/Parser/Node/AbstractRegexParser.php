<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

use PhpAnonymizer\Anonymizer\Exception\InvalidRegExpException;
use PhpAnonymizer\Anonymizer\Model\NodeParsingResult;
use Safe\Exceptions\PcreException;
use function Safe\preg_match;

abstract class AbstractRegexParser implements NodeParserInterface
{
    public function __construct(private readonly string $regexp)
    {
        // @codeCoverageIgnoreStart
        try {
            preg_match($this->regexp, '');
        } catch (PcreException $ex) {
            throw new InvalidRegExpException(
                message: \sprintf('Invalid regular expression: "%s"', $this->regexp),
                previous: $ex,
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws PcreException
     */
    public function parseNodeName(string $nodeName): NodeParsingResult
    {
        if (preg_match($this->regexp, $nodeName, $matches) === 0) {
            return new NodeParsingResult(false);
        }

        return new NodeParsingResult(
            true,
            !empty($matches['array']),
            $matches['property'],
            !empty($matches['data_access']) ? $matches['data_access'] : null,
            !empty($matches['value']) ? $matches['value'] : null,
        );
    }
}
