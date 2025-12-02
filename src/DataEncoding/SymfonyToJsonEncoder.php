<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding;

use JsonException;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class SymfonyToJsonEncoder implements DataEncoderInterface
{
    /**
     * @param NormalizerInterface $normalizer
     */
    public function __construct(
        private mixed $normalizer,
        DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        if (!$dependencyChecker->extensionIsLoaded('json')) {
            throw new MissingPlatformRequirementsException('The json extension is required for this encoder');
        }

        if (!$dependencyChecker->libraryIsInstalled('symfony/serializer')) {
            throw new MissingPlatformRequirementsException('The symfony/serializer package is required for this encoder');
        }

        if (!$normalizer instanceof NormalizerInterface) {
            throw new InvalidArgumentException('Normalizer object must implement the Symfony NormalizerInterface');
        }
    }

    /**
     * @return array<int|string,mixed>
     */
    public function decode(mixed $data, TempStorage $tempStorage): array
    {
        if (!is_object($data)) {
            throw new DataEncodingException('SymfonyEncoder can only decode objects');
        }

        $tempStorage->store('symfony-encoder-type', $data::class);

        try {
            return $this->normalizer->normalize($data);
            // @codeCoverageIgnoreStart
        } catch (ExceptionInterface $ex) {
            throw new DataEncodingException(
                message: 'Failed to decode data with Symfony Normalizer',
                previous: $ex,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    public function encode(mixed $data, TempStorage $tempStorage): string
    {
        if (!is_array($data)) {
            throw new DataEncodingException('SymfonyToJsonEncoder can only encode arrays');
        }

        try {
            /** @var array<int|string,mixed> $data */
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
        return is_object($data);
    }
}
