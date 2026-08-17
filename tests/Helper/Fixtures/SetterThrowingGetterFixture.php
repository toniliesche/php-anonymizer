<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

use Error;

final class SetterThrowingGetterFixture
{
    private string $value;

    public function getValue(): string
    {
        throw new Error('boom');
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return array<string, string>
     */
    public function export(): array
    {
        return [
            'value' => $this->value,
        ];
    }
}
