<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer;

use PhpAnonymizer\Anonymizer\Enum\DataAccess;

interface AnonymizerInterface
{
    /**
     * @template T
     *
     * @param T $data
     *
     * @return T
     */
    public function run(string $ruleSetName, mixed $data, ?string $encoding = null): mixed;

    /**
     * @param array<mixed> $definitions
     */
    public function registerRuleSet(string $name, array $definitions, string $defaultDataAccess = DataAccess::ARRAY->value): void;
}
