<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

class SimpleRegexParser extends AbstractRegexParser
{
    public function __construct()
    {
        parent::__construct('/^(?<array>\[])?(?<property>[0-9a-zA-Z.\-_]+)$/');
    }
}
