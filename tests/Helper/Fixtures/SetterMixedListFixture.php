<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class SetterMixedListFixture
{
    /** @var array<int, int> */
    private array $values = [1, 2, 3];

    /**
     * @return array<int, int>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array<int, int> $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }
}
