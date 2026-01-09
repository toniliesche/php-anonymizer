<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class PropertyMapArrayFixture
{
    /** @var array<string, string> */
    public array $meta = [
        'foo' => 'bar',
        'baz' => 'qux',
    ];
}
