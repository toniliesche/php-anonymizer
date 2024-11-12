<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding;

use JsonException;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use function is_array;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class ArrayToJsonEncoder implements DataEncoderInterface
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
        if (!is_array($data)) {
            throw new DataEncodingException('ArrayToJsonEncoder can only decode arrays');
        }

        return $data;
    }

    /**
     * @param array<int|string,mixed> $data
     */
    public function encode(mixed $data, TempStorage $tempStorage): string
    {
        if (!is_array($data)) {
            throw new DataEncodingException('ArrayToJsonEncoder can only encode arrays');
        }

        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $ex) {
            throw new DataEncodingException(
                message: 'Failed to encode data to JSON',
                previous: $ex,
            );
        }
    }

    public function getOverrideDataAccess(): ?string
    {
        return DataAccess::ARRAY->value;
    }

    public function supports(mixed $data): bool
    {
        return is_array($data);
    }
}
