<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Model;

use PhpAnonymizer\Anonymizer\DataEncoding\DataEncoderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\NoOpEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;

final class NoOpEncodingProvider implements DataEncodingProviderInterface
{
    public function provideEncoder(?string $type): DataEncoderInterface
    {
        return new NoOpEncoder();
    }
}
