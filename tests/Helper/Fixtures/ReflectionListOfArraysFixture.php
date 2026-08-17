<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class ReflectionListOfArraysFixture
{
    /** @var array<int, array<string, string>> */
    private array $items = [
        ['foo' => 'bar'],
    ];

    /**
     * @return array<int, array<string, string>>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<int, array<string, string>> $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
