<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\RuleLoader;

use Generator;

interface RuleLoaderInterface
{
    /**
     * @return Generator<string, mixed>
     */
    public function loadRules(): Generator;
}
