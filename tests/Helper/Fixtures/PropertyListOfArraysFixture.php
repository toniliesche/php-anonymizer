<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class PropertyListOfArraysFixture
{
    /** @var array<int, array<string, string>> */
    public array $items = [
        ['foo' => 'bar'],
    ];
}
