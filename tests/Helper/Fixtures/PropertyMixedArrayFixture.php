<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class PropertyMixedArrayFixture
{
    /** @var array<int|string, string> */
    public array $mixed = [
        'foo' => 'bar',
        1 => 'baz',
    ];
}
