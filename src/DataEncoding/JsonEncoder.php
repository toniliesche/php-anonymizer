<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding;

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use Safe\Exceptions\JsonException;
use function is_array;
use function is_string;
use function Safe\json_decode;
use function Safe\json_encode;

final readonly class JsonEncoder implements DataEncoderInterface
{
    public function __construct(DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker())
    {
        if (!$dependencyChecker->extensionIsLoaded('json')) {
            throw new MissingPlatformRequirementsException('The json extension is required for this encoder');
        }
    }

    /**
     * @return array<int|string,mixed>
     */
    public function decode(mixed $data, TempStorage $tempStorage): array
    {
        if (!is_string($data)) {
            throw new DataEncodingException('JsonEncoder can only decode strings');
        }

        try {
            return json_decode($data, true);
        } catch (JsonException $ex) {
            throw new DataEncodingException(
                message: 'Failed to decode JSON data',
                previous: $ex,
            );
        }
    }

    public function encode(mixed $data, TempStorage $tempStorage): string
    {
        if (!is_array($data)) {
            throw new DataEncodingException('JsonEncoder can only encode arrays');
        }

        try {
            /** @var array<int|string,mixed> $data */
            return json_encode($data);
        } catch (JsonException $ex) {
            throw new DataEncodingException(
                message: 'Failed to encode data to JSON',
                previous: $ex,
            );
        }
    }

    public function getOverrideDataAccess(): string
    {
        return DataAccess::ARRAY->value;
    }

    public function supports(mixed $data): bool
    {
        return is_string($data);
    }
}
