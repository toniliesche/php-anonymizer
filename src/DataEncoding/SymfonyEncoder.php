<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding;

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class SymfonyEncoder implements DataEncoderInterface
{
    /**
     * @param NormalizerInterface $normalizer
     * @param DenormalizerInterface $denormalizer
     */
    public function __construct(
        private mixed $normalizer,
        private mixed $denormalizer,
        DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        if (!$dependencyChecker->libraryIsInstalled('symfony/serializer')) {
            throw new MissingPlatformRequirementsException('The symfony/serializer package is required for this encoder');
        }

        if (!$normalizer instanceof NormalizerInterface) {
            throw new InvalidArgumentException('Normalizer object must implement the Symfony NormalizerInterface');
        }

        if (!$denormalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('Denormalizer object must implement the Symfony DenormalizerInterface');
        }
    }

    /**
     * @return array<int|string,mixed>
     */
    public function decode(mixed $data, TempStorage $tempStorage): array
    {
        if (!\is_object($data)) {
            throw new DataEncodingException('SymfonyEncoder can only decode objects');
        }

        $tempStorage->store('symfony-encoder-type', \get_class($data));

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

    /**
     * @param array<int|string,mixed> $data
     */
    public function encode(mixed $data, TempStorage $tempStorage): object
    {
        if (!\is_array($data)) {
            throw new DataEncodingException('SymfonyEncoder can only encode arrays');
        }

        $type = $tempStorage->retrieve('symfony-encoder-type');

        try {
            return $this->denormalizer->denormalize($data, $type);
            // @codeCoverageIgnoreStart
        } catch (ExceptionInterface $ex) {
            throw new DataEncodingException(
                message: 'Failed to encode data with Symfony Denormalizer',
                previous: $ex,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    public function getOverrideDataAccess(): ?string
    {
        return DataAccess::ARRAY->value;
    }

    public function supports(mixed $data): bool
    {
        return \is_object($data);
    }
}
