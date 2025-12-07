<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Serializer;

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\NamingSchema;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Serializer\NameConverter\MethodToVariableNameConverter;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class SerializerBuilder
{
    private const PROPERTY_INFO_PACKAGE = 'symfony/property-info';

    private string $methodNamingSchema = NamingSchema::CAMEL_CASE->value;

    private string $variableNamingSchema = NamingSchema::CAMEL_CASE->value;

    private bool $isserPrefixSupport = false;

    private bool $useAttributeResolver = false;

    private bool $usePhpDocsResolver = false;

    private bool $useReflectionResolver = false;

    private bool $useJson = false;

    private bool $useXml = false;

    private bool $useYaml = false;

    /** @var NormalizerInterface[] */
    private array $extraNormalizers = [];

    /** @var DenormalizerInterface[] */
    private array $extraDenormalizers = [];

    /** @var EncoderInterface[] */
    private array $extraEncoders = [];

    /** @var DecoderInterface[] */
    private array $extraDecoders = [];

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
            ->withJsonEncoder()
            ->withoutXmlEncoder()
            ->withoutYamlEncoder()
            ->withAttributeResolver()
            ->withPhpDocsResolver()
            ->withReflectionResolver()
            ->withoutExtraEncoders()
            ->withoutExtraDecoders()
            ->withoutExtraNormalizers()
            ->withoutExtraDenormalizers()
            ->withIsserPrefixSupport()
            ->withMethodNameSchema(NamingSchema::CAMEL_CASE->value)
            ->withVariableNameSchema(NamingSchema::CAMEL_CASE->value);
    }

    public function withMethodNameSchema(string $methodNameSchema): self
    {
        $this->methodNamingSchema = $methodNameSchema;

        return $this;
    }

    public function withVariableNameSchema(string $variableNameSchema): self
    {
        $this->variableNamingSchema = $variableNameSchema;

        return $this;
    }

    public function withIsserPrefixSupport(): self
    {
        $this->isserPrefixSupport = true;

        return $this;
    }

    public function withoutIsserPrefixSupport(): self
    {
        $this->isserPrefixSupport = false;

        return $this;
    }

    public function withAttributeResolver(): self
    {
        if (!$this->dependencyChecker->libraryIsInstalled(self::PROPERTY_INFO_PACKAGE)) {
            throw new MissingPlatformRequirementsException('The symfony/property-info package is required for the attribute extraction');
        }

        $this->useAttributeResolver = true;

        return $this;
    }

    public function withoutAttributeResolver(): self
    {
        $this->useAttributeResolver = false;

        return $this;
    }

    public function withPhpDocsResolver(): self
    {
        if (!$this->dependencyChecker->libraryIsInstalled(self::PROPERTY_INFO_PACKAGE)) {
            throw new MissingPlatformRequirementsException('The symfony/property-info package is required for the php doc extraction');
        }

        $this->usePhpDocsResolver = true;

        return $this;
    }

    public function withoutPhpDocsResolver(): self
    {
        $this->usePhpDocsResolver = false;

        return $this;
    }

    public function withReflectionResolver(): self
    {
        if (!$this->dependencyChecker->libraryIsInstalled(self::PROPERTY_INFO_PACKAGE)) {
            throw new MissingPlatformRequirementsException('The symfony/property-info package is required for the reflection extraction');
        }

        $this->useReflectionResolver = true;

        return $this;
    }

    public function withoutReflectionResolver(): self
    {
        $this->useReflectionResolver = false;

        return $this;
    }

    public function withJsonEncoder(): self
    {
        if (!$this->dependencyChecker->extensionIsLoaded('json')) {
            throw new MissingPlatformRequirementsException('The json extension is required for this encoder');
        }

        $this->useJson = true;

        return $this;
    }

    public function withoutJsonEncoder(): self
    {
        $this->useJson = false;

        return $this;
    }

    public function withXmlEncoder(): self
    {
        if (!$this->dependencyChecker->extensionIsLoaded('dom')) {
            throw new MissingPlatformRequirementsException('The dom extension is required for this encoder');
        }

        $this->useXml = true;

        return $this;
    }

    public function withoutXmlEncoder(): self
    {
        $this->useXml = false;

        return $this;
    }

    public function withYamlEncoder(): self
    {
        if (!$this->dependencyChecker->extensionIsLoaded('yaml')) {
            throw new MissingPlatformRequirementsException('The yaml extension is required for this encoder');
        }

        $this->useYaml = true;

        return $this;
    }

    public function withoutYamlEncoder(): self
    {
        $this->useYaml = false;

        return $this;
    }

    public function addExtraEncoder(EncoderInterface $encoder): self
    {
        $this->extraEncoders[] = $encoder;

        return $this;
    }

    public function withoutExtraEncoders(): self
    {
        $this->extraEncoders = [];

        return $this;
    }

    public function addExtraDecoder(DecoderInterface $decoder): self
    {
        $this->extraDecoders[] = $decoder;

        return $this;
    }

    public function withoutExtraDecoders(): self
    {
        $this->extraDecoders = [];

        return $this;
    }

    public function addExtraNormalizer(NormalizerInterface $normalizer): self
    {
        $this->extraNormalizers[] = $normalizer;

        return $this;
    }

    public function withoutExtraNormalizers(): self
    {
        $this->extraNormalizers = [];

        return $this;
    }

    public function addExtraDenormalizer(DenormalizerInterface $denormalizer): self
    {
        $this->extraDenormalizers[] = $denormalizer;

        return $this;
    }

    public function withoutExtraDenormalizers(): self
    {
        $this->extraDenormalizers = [];

        return $this;
    }

    public function build(): Serializer
    {
        $propertyAccessor = new PropertyAccessor();

        $typeExtractors = [];
        $descExtractors = [];
        $accessExtractors = [];
        $initExtractors = [];

        if ($this->usePhpDocsResolver) {
            $phpDoc = new PhpDocExtractor();
            $typeExtractors[] = $phpDoc;
            $descExtractors[] = $phpDoc;
        }

        if ($this->useReflectionResolver) {
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

        if ($this->useAttributeResolver) {
            $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());

            $varNameConverter = new MethodToVariableNameConverter(
                methodNamingSchema: $this->methodNamingSchema,
                varNamingSchema: $this->variableNamingSchema,
                isserPrefix: $this->isserPrefixSupport,
            );

            $metadataFactory = new MethodAwareMetadataFactory(
                classMetadataFactory: $classMetadataFactory,
                methodToVariableNameConverter: $varNameConverter,
            );

            $nameConverter = new MetadataAwareNameConverter(
                $classMetadataFactory,
            );
        }

        $normalizers = [
            new ObjectNormalizer(
                classMetadataFactory: $metadataFactory ?? null,
                nameConverter: $nameConverter ?? null,
                propertyAccessor: $propertyAccessor,
                propertyTypeExtractor: $propertyInfo,
            ),
        ];

        array_push($normalizers, ...$this->extraNormalizers, ...$this->extraDenormalizers);

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

        array_push($encoders, ...$this->extraEncoders, ...$this->extraDecoders);

        return new Serializer($normalizers, $encoders);
    }
}
