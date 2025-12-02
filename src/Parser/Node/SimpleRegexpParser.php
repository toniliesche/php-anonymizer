<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

/**
 * @deprecated - part of deprecated RegexpRuleSetParser
 */
final class SimpleRegexpParser extends AbstractRegexpParser
{
    public function __construct()
    {
        parent::__construct('/^(?<array>\[])?(?<property>[0-9a-zA-Z.\-_]+)$/');
    }
}
