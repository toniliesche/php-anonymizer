<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class SetterMapArrayFixture
{
    /** @var array<string, string> */
    private array $meta = [
        'foo' => 'bar',
    ];

    /**
     * @return array<string, string>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array<string, string> $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }
}
