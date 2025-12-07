<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\RuleLoader;

use Generator;

interface RuleLoaderInterface
{
    public function loadRules(): Generator;
}
