<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding\Provider;

use PhpAnonymizer\Anonymizer\DataEncoding\DataEncoderInterface;

interface DataEncodingProviderInterface
{
    public function provideEncoder(?string $type): DataEncoderInterface;
}
