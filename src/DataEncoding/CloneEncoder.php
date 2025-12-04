<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding;

use PhpAnonymizer\Anonymizer\Model\TempStorage;
use function DeepCopy\deep_copy;

final class CloneEncoder implements DataEncoderInterface
{
    public function decode(mixed $data, TempStorage $tempStorage): mixed
    {
        return deep_copy($data);
    }

    public function encode(mixed $data, TempStorage $tempStorage): mixed
    {
        return $data;
    }

    public function getOverrideDataAccess(): ?string
    {
        return null;
    }

    public function supports(mixed $data): bool
    {
        return true;
    }
}
