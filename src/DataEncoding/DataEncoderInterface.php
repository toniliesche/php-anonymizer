<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding;

use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;

interface DataEncoderInterface
{
    /**
     * @throws DataEncodingException
     */
    public function decode(mixed $data, TempStorage $tempStorage): mixed;

    /**
     * @throws DataEncodingException
     */
    public function encode(mixed $data, TempStorage $tempStorage): mixed;

    public function getOverrideDataAccess(): ?string;

    public function supports(mixed $data): bool;
}
