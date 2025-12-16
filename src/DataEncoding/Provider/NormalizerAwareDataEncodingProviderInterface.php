<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding\Provider;

interface NormalizerAwareDataEncodingProviderInterface extends DataEncodingProviderInterface
{
    public function setNormalizer(mixed $normalizer): void;

    public function setDenormalizer(mixed $denormalizer): void;
}
