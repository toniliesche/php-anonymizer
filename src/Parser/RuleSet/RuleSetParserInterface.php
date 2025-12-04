<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet;

use PhpAnonymizer\Anonymizer\Model\Tree;

interface RuleSetParserInterface
{
    /**
     * @param array<mixed> $definition
     */
    public function parseDefinition(array $definition): Tree;
}
