<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

use PhpAnonymizer\Anonymizer\Model\NodeParsingResult;

interface NodeParserInterface
{
    /**
     * @param array<mixed>|string $node
     */
    public function parseNode(array|string $node, string $path): NodeParsingResult;
}
