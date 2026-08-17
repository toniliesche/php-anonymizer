<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class AutoDetectMergeNodeFixture
{
    /** @var array<string, string> */
    public array $data = ['foo' => 'a'];

    /**
     * @return array<string, string>
     */
    public function getData(): array
    {
        return ['bar' => 'b'];
    }

    /**
     * @param array<string, string> $value
     */
    public function setData(array $value): void
    {
        $this->data = $value;
    }

    /**
     * @return array<string, string>
     */
    public function export(): array
    {
        return $this->data;
    }
}
