<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\RuleSet;

use PhpAnonymizer\Anonymizer\Model\Tree;

interface RuleSetParserInterface
{
    /**
     * @param string[] $rules
     */
    public function parseDefinition(array $rules): Tree;
}
