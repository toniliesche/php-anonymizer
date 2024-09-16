<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

use PhpAnonymizer\Anonymizer\Model\NodeParsingResult;

interface NodeParserInterface
{
    public function parseNodeName(string $nodeName): NodeParsingResult;
}
