<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Serializer;

use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Serializer\MethodAwareMetadataFactory;
use PhpAnonymizer\Anonymizer\Serializer\SerializerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class SerializerBuilderTest extends TestCase
{
    public function testCreateWillFailOnMissingSymfonySerializerPackage(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker->expects($this->once())
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new SerializerBuilder($dependencyChecker);
    }

    public function testCreateWillFailOnMissingSymfonyAccessPackage(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);

        $dependencyChecker->expects($this->exactly(2))
            ->method('libraryIsInstalled')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->expectException(MissingPlatformRequirementsException::class);
        new SerializerBuilder($dependencyChecker);
    }

    public function testEnableAttributeResolverWillFailOnMissingSymfonyInfoPackage(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);

        $dependencyChecker->expects($this->exactly(3))
            ->method('libraryIsInstalled')
            ->willReturnOnConsecutiveCalls(true, true, false);

        $builder = new SerializerBuilder($dependencyChecker);
        $this->expectException(MissingPlatformRequirementsException::class);
        $builder->withAttributeResolver();
    }

    public function testEnablePhpDocsResolverWillFailOnMissingSymfonyInfoPackage(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);

        $dependencyChecker->expects($this->exactly(3))
            ->method('libraryIsInstalled')
            ->willReturnOnConsecutiveCalls(true, true, false);

        $builder = new SerializerBuilder($dependencyChecker);
        $this->expectException(MissingPlatformRequirementsException::class);
        $builder->withPhpDocsResolver();
    }

    public function testEnableReflectionResolverWillFailOnMissingSymfonyInfoPackage(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);

        $dependencyChecker->expects($this->exactly(3))
            ->method('libraryIsInstalled')
            ->willReturnOnConsecutiveCalls(true, true, false);

        $builder = new SerializerBuilder($dependencyChecker);
        $this->expectException(MissingPlatformRequirementsException::class);
        $builder->withReflectionResolver();
    }

    public function testEnableJsonEncoderWillFailOnMissingJsonExtension(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);

        $dependencyChecker->expects($this->exactly(2))
            ->method('libraryIsInstalled')
            ->willReturn(true);

        $dependencyChecker->expects($this->once())
            ->method('extensionIsLoaded')
            ->willReturn(false);

        $builder = new SerializerBuilder($dependencyChecker);
        $this->expectException(MissingPlatformRequirementsException::class);
        $builder->withJsonEncoder();
    }

    public function testEnableXmlEncoderWillFailOnMissingDomExtension(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);

        $dependencyChecker->expects($this->exactly(2))
            ->method('libraryIsInstalled')
            ->willReturn(true);

        $dependencyChecker->expects($this->once())
            ->method('extensionIsLoaded')
            ->willReturn(false);

        $builder = new SerializerBuilder($dependencyChecker);
        $this->expectException(MissingPlatformRequirementsException::class);
        $builder->withXmlEncoder();
    }

    public function testEnableYamlEncoderWillFailOnMissingYamlExtension(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);

        $dependencyChecker->expects($this->exactly(2))
            ->method('libraryIsInstalled')
            ->willReturn(true);

        $dependencyChecker->expects($this->once())
            ->method('extensionIsLoaded')
            ->willReturn(false);

        $builder = new SerializerBuilder($dependencyChecker);
        $this->expectException(MissingPlatformRequirementsException::class);
        $builder->withYamlEncoder();
    }

    public function testCanBuildSerializer(): void
    {
        $this->expectNotToPerformAssertions();
        $builder = new SerializerBuilder();

        $builder->build();
    }

    public function testCanBuildSerializerWithDefaults(): void
    {
        $this->expectNotToPerformAssertions();
        $builder = new SerializerBuilder();
        $builder->withDefaults();

        $builder->build();
    }

    public function testCanCreateSerializerWithAttributeResolver(): void
    {
        $builder = new SerializerBuilder();
        $builder->withAttributeResolver();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof ObjectNormalizer) {
                continue;
            }
            $found = true;

            $normalizerReflection = new ReflectionClass($normalizer);
            $metadataFactory = $normalizerReflection->getProperty('classMetadataFactory')->getValue($normalizer);

            self::assertInstanceOf(MethodAwareMetadataFactory::class, $metadataFactory);

            break;
        }
        self::assertTrue($found, 'Did not find ObjectNormalizer');
    }

    public function testCanCreateSerializerWithoutAttributeResolver(): void
    {
        $builder = new SerializerBuilder();
        $builder->withoutAttributeResolver();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof ObjectNormalizer) {
                continue;
            }
            $found = true;

            $normalizerReflection = new ReflectionClass($normalizer);
            $metadataFactory = $normalizerReflection->getProperty('classMetadataFactory')->getValue($normalizer);

            self::assertNull($metadataFactory);

            break;
        }
        self::assertTrue($found, 'Did not find ObjectNormalizer');
    }

    public function testCanCreateSerializerWithPhpDocsResolver(): void
    {
        $builder = new SerializerBuilder();
        $builder->withPhpDocsResolver();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof ObjectNormalizer) {
                continue;
            }
            $found = true;

            $normalizerReflection = new ReflectionClass(AbstractObjectNormalizer::class);
            $propertyInfoExtractor = $normalizerReflection->getProperty(name: 'propertyTypeExtractor')->getValue($normalizer);
            self::assertInstanceOf(PropertyInfoExtractor::class, $propertyInfoExtractor);

            $infoExtractorReflection = new ReflectionClass($propertyInfoExtractor);

            $typeExtractors = $infoExtractorReflection->getProperty(name: 'typeExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($typeExtractors);
            $typeExtractorFound = false;
            foreach ($typeExtractors as $typeExtractor) {
                if ($typeExtractor instanceof PhpDocExtractor) {
                    $typeExtractorFound = true;

                    break;
                }
            }
            self::assertTrue($typeExtractorFound, 'Did not find PhpDocExtractor in typeExtractors');

            $descriptionExtractors = $infoExtractorReflection->getProperty(name: 'descriptionExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($descriptionExtractors);
            $descriptionExtractorFound = false;
            foreach ($descriptionExtractors as $descriptionExtractor) {
                if ($descriptionExtractor instanceof PhpDocExtractor) {
                    $descriptionExtractorFound = true;

                    break;
                }
            }
            self::assertTrue($descriptionExtractorFound, 'Did not find PhpDocExtractor in descriptionExtractors');

            break;
        }
        self::assertTrue($found, 'Did not find ObjectNormalizer');
    }

    public function testCanCreateSerializerWithoutPhpDocsResolver(): void
    {
        $builder = new SerializerBuilder();
        $builder->withoutPhpDocsResolver();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof ObjectNormalizer) {
                continue;
            }
            $found = true;

            $normalizerReflection = new ReflectionClass(AbstractObjectNormalizer::class);
            $propertyInfoExtractor = $normalizerReflection->getProperty(name: 'propertyTypeExtractor')->getValue($normalizer);
            self::assertInstanceOf(PropertyInfoExtractor::class, $propertyInfoExtractor);

            $infoExtractorReflection = new ReflectionClass($propertyInfoExtractor);

            $typeExtractors = $infoExtractorReflection->getProperty(name: 'typeExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($typeExtractors);
            $typeExtractorFound = false;
            foreach ($typeExtractors as $typeExtractor) {
                if ($typeExtractor instanceof PhpDocExtractor) {
                    $typeExtractorFound = true;

                    break;
                }
            }
            self::assertFalse($typeExtractorFound, 'Did not expect to find PhpDocExtractor in typeExtractors');

            $descriptionExtractors = $infoExtractorReflection->getProperty(name: 'descriptionExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($descriptionExtractors);
            $descriptionExtractorFound = false;
            foreach ($descriptionExtractors as $descriptionExtractor) {
                if ($descriptionExtractor instanceof PhpDocExtractor) {
                    $descriptionExtractorFound = true;

                    break;
                }
            }
            self::assertFalse($descriptionExtractorFound, 'Did not expect to find PhpDocExtractor in descriptionExtractors');

            break;
        }
        self::assertTrue($found, 'Did not find ObjectNormalizer');
    }

    public function testCanCreateSerializerWithReflectionResolver(): void
    {
        $builder = new SerializerBuilder();
        $builder->withReflectionResolver();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof ObjectNormalizer) {
                continue;
            }
            $found = true;

            $normalizerReflection = new ReflectionClass(AbstractObjectNormalizer::class);
            $propertyInfoExtractor = $normalizerReflection->getProperty(name: 'propertyTypeExtractor')->getValue($normalizer);
            self::assertInstanceOf(PropertyInfoExtractor::class, $propertyInfoExtractor);

            $infoExtractorReflection = new ReflectionClass($propertyInfoExtractor);

            $typeExtractors = $infoExtractorReflection->getProperty(name: 'typeExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($typeExtractors);
            $typeExtractorFound = false;
            foreach ($typeExtractors as $typeExtractor) {
                if ($typeExtractor instanceof ReflectionExtractor) {
                    $typeExtractorFound = true;

                    break;
                }
            }
            self::assertTrue($typeExtractorFound, 'Did not find ReflectionExtractor in typeExtractors');

            $accessExtractors = $infoExtractorReflection->getProperty(name: 'accessExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($accessExtractors);
            $accessExtractorFound = false;
            foreach ($accessExtractors as $accessExtractor) {
                if ($accessExtractor instanceof ReflectionExtractor) {
                    $accessExtractorFound = true;

                    break;
                }
            }
            self::assertTrue($accessExtractorFound, 'Did not find ReflectionExtractor in accessExtractors');

            $initializableExtractors = $infoExtractorReflection->getProperty(name: 'initializableExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($initializableExtractors);
            $initializableExtractorFound = false;
            foreach ($initializableExtractors as $initializableExtractor) {
                if ($initializableExtractor instanceof ReflectionExtractor) {
                    $initializableExtractorFound = true;

                    break;
                }
            }
            self::assertTrue($initializableExtractorFound, 'Did not find ReflectionExtractor in initializableExtractors');

            break;
        }
        self::assertTrue($found, 'Did not find ObjectNormalizer');
    }

    public function testCanCreateSerializerWithoutReflectionResolver(): void
    {
        $builder = new SerializerBuilder();
        $builder->withoutReflectionResolver();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof ObjectNormalizer) {
                continue;
            }
            $found = true;

            $normalizerReflection = new ReflectionClass(AbstractObjectNormalizer::class);
            $propertyInfoExtractor = $normalizerReflection->getProperty(name: 'propertyTypeExtractor')->getValue($normalizer);
            self::assertInstanceOf(PropertyInfoExtractor::class, $propertyInfoExtractor);

            $infoExtractorReflection = new ReflectionClass($propertyInfoExtractor);

            $typeExtractors = $infoExtractorReflection->getProperty(name: 'typeExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($typeExtractors);
            $typeExtractorFound = false;
            foreach ($typeExtractors as $typeExtractor) {
                if ($typeExtractor instanceof ReflectionExtractor) {
                    $typeExtractorFound = true;

                    break;
                }
            }
            self::assertFalse($typeExtractorFound, 'Did not expect to find ReflectionExtractor in typeExtractors');

            $accessExtractors = $infoExtractorReflection->getProperty(name: 'accessExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($accessExtractors);
            $accessExtractorFound = false;
            foreach ($accessExtractors as $accessExtractor) {
                if ($accessExtractor instanceof ReflectionExtractor) {
                    $accessExtractorFound = true;

                    break;
                }
            }
            self::assertFalse($accessExtractorFound, 'Did not expect to find ReflectionExtractor in accessExtractors');

            $initializableExtractors = $infoExtractorReflection->getProperty(name: 'initializableExtractors')->getValue($propertyInfoExtractor);
            self::assertIsArray($initializableExtractors);
            $initializableExtractorFound = false;
            foreach ($initializableExtractors as $initializableExtractor) {
                if ($initializableExtractor instanceof ReflectionExtractor) {
                    $initializableExtractorFound = true;

                    break;
                }
            }
            self::assertFalse($initializableExtractorFound, 'Did not expect to find ReflectionExtractor in initializableExtractors');

            break;
        }
        self::assertTrue($found, 'Did not find ObjectNormalizer');
    }

    public function testCanCreateSerializerWithJsonEncoder(): void
    {
        $builder = new SerializerBuilder();
        $builder->withJsonEncoder();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realEncoder = $serializerReflection->getProperty('encoder')->getValue($serializer);

        $encoderReflection = new ReflectionClass($realEncoder);
        $encoders = $encoderReflection->getProperty('encoders')->getValue($realEncoder);

        $found = false;
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof JsonEncoder) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertTrue($found, 'Did not find JsonEncoder');
    }

    public function testCanCreateSerializerWithoutJsonEncoder(): void
    {
        $builder = new SerializerBuilder();
        $builder->withoutJsonEncoder();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realEncoder = $serializerReflection->getProperty('encoder')->getValue($serializer);

        $encoderReflection = new ReflectionClass($realEncoder);
        $encoders = $encoderReflection->getProperty('encoders')->getValue($realEncoder);

        $found = false;
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof JsonEncoder) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertFalse($found, 'Did not expect to find JsonEncoder');
    }

    public function testCanCreateSerializerWithXmlEncoder(): void
    {
        $builder = new SerializerBuilder();
        $builder->withXmlEncoder();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realEncoder = $serializerReflection->getProperty('encoder')->getValue($serializer);

        $encoderReflection = new ReflectionClass($realEncoder);
        $encoders = $encoderReflection->getProperty('encoders')->getValue($realEncoder);

        $found = false;
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof XmlEncoder) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertTrue($found, 'Did not find XmlEncoder');
    }

    public function testCanCreateSerializerWithoutXmlEncoder(): void
    {
        $builder = new SerializerBuilder();
        $builder->withoutXmlEncoder();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realEncoder = $serializerReflection->getProperty('encoder')->getValue($serializer);

        $encoderReflection = new ReflectionClass($realEncoder);
        $encoders = $encoderReflection->getProperty('encoders')->getValue($realEncoder);

        $found = false;
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof XmlEncoder) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertFalse($found, 'Did not expect to find XmlEncoder');
    }

    public function testCanCreateSerializerWithYamlEncoder(): void
    {
        $builder = new SerializerBuilder();
        $builder->withYamlEncoder();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realEncoder = $serializerReflection->getProperty('encoder')->getValue($serializer);

        $encoderReflection = new ReflectionClass($realEncoder);
        $encoders = $encoderReflection->getProperty('encoders')->getValue($realEncoder);

        $found = false;
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof YamlEncoder) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertTrue($found, 'Did not find YamlEncoder');
    }

    public function testCanCreateSerializerWithoutYamlEncoder(): void
    {
        $builder = new SerializerBuilder();
        $builder->withoutYamlEncoder();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realEncoder = $serializerReflection->getProperty('encoder')->getValue($serializer);

        $encoderReflection = new ReflectionClass($realEncoder);
        $encoders = $encoderReflection->getProperty('encoders')->getValue($realEncoder);

        $found = false;
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof YamlEncoder) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertFalse($found, 'Did not expect to find YamlEncoder');
    }

    public function testCanCreateSerializerWithExtraEncoder(): void
    {
        $extraEncoder = $this->createMock(EncoderInterface::class);

        $builder = new SerializerBuilder();
        $builder->addExtraEncoder($extraEncoder);

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realEncoder = $serializerReflection->getProperty('encoder')->getValue($serializer);

        $encoderReflection = new ReflectionClass($realEncoder);
        $encoders = $encoderReflection->getProperty('encoders')->getValue($realEncoder);
        self::assertIsArray($encoders);

        $found = false;
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof MockObject) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertTrue($found, 'Did not find ExtraEncoder');
    }

    public function testCanCreateSerializerWithoutExtraEncoder(): void
    {
        $extraEncoder = $this->createMock(EncoderInterface::class);

        $builder = new SerializerBuilder();
        $builder->addExtraEncoder($extraEncoder);
        $builder->withoutExtraEncoders();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realEncoder = $serializerReflection->getProperty('encoder')->getValue($serializer);

        $encoderReflection = new ReflectionClass($realEncoder);
        $encoders = $encoderReflection->getProperty('encoders')->getValue($realEncoder);
        self::assertIsArray($encoders);

        $found = false;
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof MockObject) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertFalse($found, 'Did not expect to find ExtraEncoder');
    }

    public function testCanCreateSerializerWithExtraDecoder(): void
    {
        $extraDecoder = $this->createMock(DecoderInterface::class);

        $builder = new SerializerBuilder();
        $builder->addExtraDecoder($extraDecoder);

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realDecoder = $serializerReflection->getProperty('decoder')->getValue($serializer);

        $decoderReflection = new ReflectionClass($realDecoder);
        $decoders = $decoderReflection->getProperty('decoders')->getValue($realDecoder);
        self::assertIsArray($decoders);

        $found = false;
        foreach ($decoders as $decoder) {
            if (!$decoder instanceof MockObject) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertTrue($found, 'Did not find ExtraDecoder');
    }

    public function testCanCreateSerializerWithoutExtraDecoder(): void
    {
        $extraDecoder = $this->createMock(DecoderInterface::class);

        $builder = new SerializerBuilder();
        $builder->addExtraDecoder($extraDecoder);
        $builder->withoutExtraDecoders();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $realDecoder = $serializerReflection->getProperty('decoder')->getValue($serializer);

        $decoderReflection = new ReflectionClass($realDecoder);
        $decoders = $decoderReflection->getProperty('decoders')->getValue($realDecoder);
        self::assertIsArray($decoders);

        $found = false;
        foreach ($decoders as $decoder) {
            if (!$decoder instanceof MockObject) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertFalse($found, 'Did not expect to find ExtraDecoder');
    }

    public function testCanCreateSerializerWithExtraNormalizer(): void
    {
        $extraNormalizer = $this->createMock(NormalizerInterface::class);

        $builder = new SerializerBuilder();
        $builder->addExtraNormalizer($extraNormalizer);

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);
        self::assertIsArray($normalizers);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof MockObject) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertTrue($found, 'Did not find ExtraNormalizer');
    }

    public function testCanCreateSerializerWithoutExtraNormalizer(): void
    {
        $extraNormalizer = $this->createMock(NormalizerInterface::class);

        $builder = new SerializerBuilder();
        $builder->addExtraNormalizer($extraNormalizer);
        $builder->withoutExtraNormalizers();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);
        self::assertIsArray($normalizers);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof MockObject) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertFalse($found, 'Did not expect to find ExtraNormalizer');
    }

    public function testCanCreateSerializerWithExtraDenormalizer(): void
    {
        $extraDenormalizer = $this->createMock(DenormalizerInterface::class);

        $builder = new SerializerBuilder();
        $builder->addExtraDenormalizer($extraDenormalizer);

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $denormalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);
        self::assertIsArray($denormalizers);

        $found = false;
        foreach ($denormalizers as $denormalizer) {
            if (!$denormalizer instanceof MockObject) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertTrue($found, 'Did not find ExtraDenormalizer');
    }

    public function testCanCreateSerializerWithoutExtraDenormalizer(): void
    {
        $extraDenormalizer = $this->createMock(DenormalizerInterface::class);

        $builder = new SerializerBuilder();
        $builder->addExtraDenormalizer($extraDenormalizer);
        $builder->withoutExtraDenormalizers();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $denormalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);
        self::assertIsArray($denormalizers);

        $found = false;
        foreach ($denormalizers as $denormalizer) {
            if (!$denormalizer instanceof MockObject) {
                continue;
            }

            $found = true;

            break;
        }
        self::assertFalse($found, 'Did not expect to find ExtraDenormalizer');
    }

    public function testCanCreateSerializerWithIsserPrefixSupport(): void
    {
        $builder = new SerializerBuilder();
        $builder->withAttributeResolver();
        $builder->withIsserPrefixSupport();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof ObjectNormalizer) {
                continue;
            }
            $found = true;

            $normalizerReflection = new ReflectionClass($normalizer);
            $metadataFactory = $normalizerReflection->getProperty('classMetadataFactory')->getValue($normalizer);
            self::assertInstanceOf(MethodAwareMetadataFactory::class, $metadataFactory);

            $metadataFactoryReflection = new ReflectionClass($metadataFactory);
            $varNameConverter = $metadataFactoryReflection->getProperty('methodToVariableNameConverter')->getValue($metadataFactory);

            $varNameConverterReflection = new ReflectionClass($varNameConverter);
            $isserPrefix = $varNameConverterReflection->getProperty('isserPrefix')->getValue($varNameConverter);
            self::assertTrue($isserPrefix);

            break;
        }
        self::assertTrue($found, 'Did not find ObjectNormalizer');
    }

    public function testCanCreateSerializerWithoutIsserPrefixSupport(): void
    {
        $builder = new SerializerBuilder();
        $builder->withAttributeResolver();
        $builder->withoutIsserPrefixSupport();

        $serializer = $builder->build();

        $serializerReflection = new ReflectionClass($serializer);
        $normalizers = $serializerReflection->getProperty('normalizers')->getValue($serializer);

        $found = false;
        foreach ($normalizers as $normalizer) {
            if (!$normalizer instanceof ObjectNormalizer) {
                continue;
            }
            $found = true;

            $normalizerReflection = new ReflectionClass($normalizer);
            $metadataFactory = $normalizerReflection->getProperty('classMetadataFactory')->getValue($normalizer);
            self::assertInstanceOf(MethodAwareMetadataFactory::class, $metadataFactory);

            $metadataFactoryReflection = new ReflectionClass($metadataFactory);
            $varNameConverter = $metadataFactoryReflection->getProperty('methodToVariableNameConverter')->getValue($metadataFactory);

            $varNameConverterReflection = new ReflectionClass($varNameConverter);
            $isserPrefix = $varNameConverterReflection->getProperty('isserPrefix')->getValue($varNameConverter);
            self::assertFalse($isserPrefix);

            break;
        }
        self::assertTrue($found, 'Did not find ObjectNormalizer');
    }
}
