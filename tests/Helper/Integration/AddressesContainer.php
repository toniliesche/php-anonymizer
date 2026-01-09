<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Integration;

use PhpAnonymizer\Anonymizer\Test\Helper\Model\Address;

final class AddressesContainer
{
    /**
     * @param Address[] $addresses
     */
    public function __construct(public array $addresses)
    {
    }
}
