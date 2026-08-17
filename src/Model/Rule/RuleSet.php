<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model\Rule;

final readonly class RuleSet
{
    public function __construct(
        public Tree $tree,
        public string $defaultDataAccess,
        public bool $denyList = true,
    ) {
    }
}
