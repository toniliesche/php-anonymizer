<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class AutoDetectMergeLeafNodeFixture
{
    public string $item = 'value';

    public function getItem(): AutoDetectMergeLeafNodeItem
    {
        return new AutoDetectMergeLeafNodeItem();
    }

    public function setItem(string $item): void
    {
        $this->item = $item;
    }
}
