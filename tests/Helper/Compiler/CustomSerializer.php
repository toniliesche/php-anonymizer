<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Compiler;

use Symfony\Component\Serializer\SerializerInterface;

final class CustomSerializer implements SerializerInterface
{
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return '';
    }

    public function deserialize(
        mixed $data,
        string $type,
        string $format,
        array $context = [],
    ): mixed {
        return null;
    }
}
