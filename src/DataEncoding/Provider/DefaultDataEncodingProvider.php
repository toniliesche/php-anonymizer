<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataEncoding\Provider;

use PhpAnonymizer\Anonymizer\DataEncoding\ArrayToJsonEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\CloneEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\DataEncoderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\JsonEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\NoOpEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\SymfonyEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\SymfonyToArrayEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\SymfonyToJsonEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\YamlEncoder;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataEncoder;
use PhpAnonymizer\Anonymizer\Exception\DataEncoderExistsException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Exception\MissingProviderRequirementException;
use PhpAnonymizer\Anonymizer\Exception\UnknownDataEncoderException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function in_array;
use function sprintf;

class DefaultDataEncodingProvider implements DataEncodingProviderInterface
{
    private const ENCODERS = [
        DataEncoder::CLONE->value,
        DataEncoder::JSON->value,
        DataEncoder::NOOP->value,
        DataEncoder::SYMFONY->value,
        DataEncoder::YAML->value,
    ];

    /** @var array<string, DataEncoderInterface> */
    private array $dataEncoders = [];

    /** @var string[] */
    private array $customDataEncoders = [];

    /**
     * @param null|NormalizerInterface $normalizer
     * @param null|DenormalizerInterface $denormalizer
     */
    public function __construct(
        private mixed $normalizer = null,
        private mixed $denormalizer = null,
        private readonly DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        if ($normalizer !== null) {
            if (!$this->dependencyChecker->libraryIsInstalled('symfony/serializer')) {
                throw new MissingPlatformRequirementsException('The symfony/serializer package is required to work with normalizers');
            }

            if (!$normalizer instanceof NormalizerInterface) {
                throw new InvalidArgumentException('Normalizer object must implement the Symfony NormalizerInterface');
            }
        }

        if ($denormalizer !== null) {
            if (!$this->dependencyChecker->libraryIsInstalled('symfony/serializer')) {
                throw new MissingPlatformRequirementsException('The symfony/serializer package is required to work with denormalizers');
            }

            if (!$denormalizer instanceof DenormalizerInterface) {
                throw new InvalidArgumentException('Denormalizer object must implement the Symfony DenormalizerInterface');
            }
        }
    }

    /**
     * @param NormalizerInterface $normalizer
     */
    public function setNormalizer(mixed $normalizer): void
    {
        if (!$this->dependencyChecker->libraryIsInstalled('symfony/serializer')) {
            throw new MissingPlatformRequirementsException('The symfony/serializer package is required to work with denormalizers');
        }

        if (!$normalizer instanceof NormalizerInterface) {
            throw new InvalidArgumentException('Normalizer object must implement the Symfony NormalizerInterface');
        }

        $this->normalizer = $normalizer;
    }

    /**
     * @param DenormalizerInterface $denormalizer
     */
    public function setDenormalizer(mixed $denormalizer): void
    {
        if (!$this->dependencyChecker->libraryIsInstalled('symfony/serializer')) {
            throw new MissingPlatformRequirementsException('The symfony/serializer package is required to work with denormalizers');
        }

        if (!$denormalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('Denormalizer object must implement the Symfony DenormalizerInterface');
        }

        $this->denormalizer = $denormalizer;
    }

    public function registerCustomDataEncoder(string $name, DataEncoderInterface $encoder): void
    {
        if (in_array($name, self::ENCODERS, true) || in_array($name, $this->customDataEncoders, true)) {
            throw new DataEncoderExistsException(sprintf('Cannot override existing data encoder: "%s"', $name));
        }

        $this->customDataEncoders[] = $name;
        $this->dataEncoders[$name] = $encoder;
    }

    public function provideEncoder(?string $type): DataEncoderInterface
    {
        $type ??= DataEncoder::CLONE->value;
        if (!isset($this->dataEncoders[$type])) {
            $this->dataEncoders[$type] = $this->resolveEncoder($type);
        }

        return $this->dataEncoders[$type];
    }

    private function resolveEncoder(string $type): DataEncoderInterface
    {
        return match ($type) {
            DataEncoder::ARRAY_TO_JSON->value => new ArrayToJsonEncoder(),
            DataEncoder::CLONE->value => new CloneEncoder(),
            DataEncoder::JSON->value => new JsonEncoder(),
            DataEncoder::NOOP->value => new NoOpEncoder(),
            DataEncoder::SYMFONY->value => new SymfonyEncoder(
                $this->normalizer ?? throw new MissingProviderRequirementException('SymfonyEncoder needs an instance of NormalizerInterface to be instantiated'),
                $this->denormalizer ?? throw new MissingProviderRequirementException('SymfonyEncoder needs an instance of DenormalizerInterface to be instantiated'),
            ),
            DataEncoder::SYMFONY_TO_ARRAY->value => new SymfonyToArrayEncoder(
                $this->normalizer ?? throw new MissingProviderRequirementException('SymfonyToArrayEncoder needs an instance of NormalizerInterface to be instantiated'),
            ),
            DataEncoder::SYMFONY_TO_JSON->value => new SymfonyToJsonEncoder(
                $this->normalizer ?? throw new MissingProviderRequirementException('SymfonyToJsonEncoder needs an instance of NormalizerInterface to be instantiated'),
            ),
            DataEncoder::YAML->value => new YamlEncoder(),
            default => throw new UnknownDataEncoderException(sprintf('Unknown data encoder: "%s"', $type)),
        };
    }
}
