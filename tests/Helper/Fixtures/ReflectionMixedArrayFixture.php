<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class ReflectionMixedArrayFixture
{
    /** @var array<int|string, string> */
    private array $mixed = [
        'foo' => 'bar',
        1 => 'baz',
    ];

    /**
     * @return array<int|string, string>
     */
    public function getMixed(): array
    {
        return $this->mixed;
    }

    /**
     * @param array<int|string, string> $mixed
     */
    public function setMixed(array $mixed): void
    {
        $this->mixed = $mixed;
    }
}
