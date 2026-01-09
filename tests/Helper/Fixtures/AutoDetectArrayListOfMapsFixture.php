<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class AutoDetectArrayListOfMapsFixture
{
    /** @var array<int, array<string, string>> */
    public array $items = [
        ['name' => 'John', 'city' => 'New York'],
        ['name' => 'Jane', 'city' => 'Los Angeles'],
    ];
}
