<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Examples;

class OrderWithJsonAddress
{
    public function __construct(public string $address)
    {
    }
}
