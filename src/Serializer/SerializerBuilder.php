<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer;

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class SerializerBuilder
{
    private bool $usePhpDoc = false;

    private bool $useReflection = false;

    private bool $useJson = false;

    private bool $useXml = false;

    private bool $useYaml = false;

    /** @var NormalizerInterface[] */
    private array $extraNormalizers = [];

    /** @var EncoderInterface[] */
    private array $extraEncoders = [];

    public function __construct(
        private readonly DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
        if (!$this->dependencyChecker->libraryIsInstalled('symfony/serializer')) {
            throw new MissingPlatformRequirementsException('The symfony/serializer package is required for the serializer');
        }

        if (!$this->dependencyChecker->libraryIsInstalled('symfony/property-access')) {
            throw new MissingPlatformRequirementsException('The symfony/property-access package is required for the serializer');
        }
    }

    public function withDefaults(): self
    {
        return $this
            ->withJson()
            ->withPhpDoc()
            ->withReflection();
    }

    public function withPhpDoc(): self
    {
        if (!$this->dependencyChecker->libraryIsInstalled('symfony/property-info')) {
            throw new MissingPlatformRequirementsException('The symfony/property-info package is required for the php doc extraction');
        }

        $clone = clone $this;
        $clone->usePhpDoc = true;

        return $clone;
    }

    public function withoutPhpDoc(): self
    {
        $clone = clone $this;
        $clone->usePhpDoc = false;

        return $clone;
    }

    public function withReflection(): self
    {
        if (!$this->dependencyChecker->libraryIsInstalled('symfony/property-info')) {
            throw new MissingPlatformRequirementsException('The symfony/property-info package is required for the reflection extraction');
        }

        $clone = clone $this;
        $clone->useReflection = true;

        return $clone;
    }

    public function withoutReflection(): self
    {
        $clone = clone $this;
        $clone->useReflection = false;

        return $clone;
    }

    public function withJson(): self
    {
        $clone = clone $this;
        $clone->useJson = true;

        return $clone;
    }

    public function withoutJson(): self
    {
        $clone = clone $this;
        $clone->useJson = false;

        return $clone;
    }

    public function withXml(): self
    {
        $clone = clone $this;
        $clone->useXml = true;

        return $clone;
    }

    public function withoutXml(): self
    {
        $clone = clone $this;
        $clone->useXml = false;

        return $clone;
    }

    public function withYaml(): self
    {
        $clone = clone $this;
        $clone->useYaml = true;

        return $clone;
    }

    public function withoutYaml(): self
    {
        $clone = clone $this;
        $clone->useYaml = false;

        return $clone;
    }

    public function addNormalizer(NormalizerInterface $normalizer): self
    {
        $clone = clone $this;
        $clone->extraNormalizers[] = $normalizer;

        return $clone;
    }

    public function addEncoder(EncoderInterface $encoder): self
    {
        $clone = clone $this;
        $clone->extraEncoders[] = $encoder;

        return $clone;
    }

    public function build(): Serializer
    {
        $propertyAccessor = new PropertyAccessor();

        $typeExtractors = [];
        $descExtractors = [];
        $accessExtractors = [];
        $initExtractors = [];

        if ($this->usePhpDoc) {
            $phpDoc = new PhpDocExtractor();
            $typeExtractors[] = $phpDoc;
            $descExtractors[] = $phpDoc;
        }

        if ($this->useReflection) {
            $reflection = new ReflectionExtractor();
            $typeExtractors[] = $reflection;
            $accessExtractors[] = $reflection;
            $initExtractors[] = $reflection;
        }

        $propertyInfo = new PropertyInfoExtractor(
            typeExtractors: $typeExtractors,
            descriptionExtractors: $descExtractors,
            accessExtractors: $accessExtractors,
            initializableExtractors: $initExtractors,
        );

        $normalizers = [
            new ObjectNormalizer(
                propertyAccessor: $propertyAccessor,
                propertyTypeExtractor: $propertyInfo,
            ),
        ];

        array_push($normalizers, ...$this->extraNormalizers);

        $encoders = [];

        if ($this->useJson) {
            $encoders[] = new JsonEncoder();
        }

        if ($this->useXml) {
            $encoders[] = new XmlEncoder();
        }

        if ($this->useYaml) {
            $encoders[] = new YamlEncoder();
        }

        array_push($encoders, ...$this->extraEncoders);

        return new Serializer($normalizers, $encoders);
    }
}
