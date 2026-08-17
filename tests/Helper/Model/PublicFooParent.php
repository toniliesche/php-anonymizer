<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

final class PublicFooParent
{
    /**
     * @param array<int, mixed> $array
     */
    public function __construct(
        public PublicFoobar $foobar,
        public array $array,
    ) {
    }
}
