<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

class MixedFooParent
{
    public function __construct(
        public PublicFoobar $publicFoo,
        private Foobar $privateFoo,
    ) {
    }

    public function getPrivateFoo(): Foobar
    {
        return $this->privateFoo;
    }

    public function setPrivateFoo(Foobar $privateFoo): void
    {
        $this->privateFoo = $privateFoo;
    }
}
