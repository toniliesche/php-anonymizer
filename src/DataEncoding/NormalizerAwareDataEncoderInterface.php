<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

interface NormalizerAwareDataEncoderInterface extends DataEncoderInterface
{
    /**
     * @param DenormalizerInterface $denormalizer
     */
    public function setDenormalizer(mixed $denormalizer): void;

    /**
     * @param NormalizerInterface $normalizer
     */
    public function setNormalizer(mixed $normalizer): void;
}
