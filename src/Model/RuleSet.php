<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

readonly class RuleSet
{
    public function __construct(
        public Tree $tree,
        public string $defaultDataAccess,
    ) {
    }
}
