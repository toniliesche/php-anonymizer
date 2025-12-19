<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Integration;

use Generator;
use PhpAnonymizer\Anonymizer\Anonymizer;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Config\ContainerBuilderConfig;
use PhpAnonymizer\Anonymizer\Test\Helper\DependencyInjection\Traits\ContainerBuilderTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use function sprintf;

final class AnonymizerFactoryTest extends TestCase
{
    use ContainerBuilderTrait;

    #[DataProvider('provideConfigFiles')]
    public function testCanBuildAnonymizer(string $configFile): void
    {
        $builder = $this->createBuilder(
            configFile: $configFile,
            builderConfig: (new ContainerBuilderConfig())->withCompile(),
        );

        $anonymizer = $builder->get(Anonymizer::class);
        self::assertInstanceOf(Anonymizer::class, $anonymizer);
    }

    public function testCanBuildAnonymizerWithoutSerializer(): void
    {
        $builder = $this->createBuilder(
            configFile: sprintf('%s/config/without_serializer.yaml', FIXTURES_ROOT),
            builderConfig: (new ContainerBuilderConfig()),
        );

        $builder->compile();

        $anonymizer = $builder->get(Anonymizer::class);
        list($normalizer, $denormalizer) = $this->resolveSerializerComponents($anonymizer);

        self::assertNull($normalizer);
        self::assertNull($denormalizer);
    }

    public function testCanBuildAnonymizerWithAutowiredSerializer(): void
    {
        $builder = $this->createBuilder(
            configFile: sprintf('%s/config/with_autowired_serializer.yaml', FIXTURES_ROOT),
            builderConfig: (new ContainerBuilderConfig()),
        );

        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $builder->set(SerializerInterface::class, $serializer);

        $definition = new Definition(SerializerInterface::class);
        $definition->setSynthetic(true);
        $builder->setDefinition(SerializerInterface::class, $definition);

        $mySerializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $builder->set('MySerializer', $mySerializer);

        $definition = new Definition();
        $definition->setSynthetic(true);
        $builder->setDefinition('MySerializer', $definition);

        $builder->compile();

        $anonymizer = $builder->get(Anonymizer::class);
        list($normalizer, $denormalizer) = $this->resolveSerializerComponents($anonymizer);

        self::assertNotSame($mySerializer, $normalizer);
        self::assertSame($serializer, $normalizer);

        self::assertNotSame($mySerializer, $denormalizer);
        self::assertSame($serializer, $denormalizer);
    }

    public function testCanBuildAnonymizerWithCustomSerializer(): void
    {
        $builder = $this->createBuilder(
            configFile: sprintf('%s/config/with_custom_serializer.yaml', FIXTURES_ROOT),
            builderConfig: (new ContainerBuilderConfig()),
        );

        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $builder->set(SerializerInterface::class, $serializer);

        $definition = new Definition();
        $definition->setSynthetic(true);
        $builder->setDefinition(SerializerInterface::class, $definition);

        $mySerializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $builder->set('MySerializer', $mySerializer);

        $definition = new Definition();
        $definition->setSynthetic(true);
        $builder->setDefinition('MySerializer', $definition);

        $builder->compile();

        $anonymizer = $builder->get(Anonymizer::class);
        list($normalizer, $denormalizer) = $this->resolveSerializerComponents($anonymizer);

        self::assertNotSame($serializer, $normalizer);
        self::assertSame($mySerializer, $normalizer);

        self::assertNotSame($serializer, $denormalizer);
        self::assertSame($mySerializer, $denormalizer);
    }

    public function testCanBuildAnonymizerWithInternalSerializer(): void
    {
        $builder = $this->createBuilder(
            configFile: sprintf('%s/config/complete.yaml', FIXTURES_ROOT),
            builderConfig: (new ContainerBuilderConfig()),
        );

        $serializer = new Serializer();
        $builder->set(SerializerInterface::class, $serializer);

        $definition = new Definition();
        $definition->setSynthetic(true);
        $builder->setDefinition(SerializerInterface::class, $definition);

        $mySerializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $builder->set('MySerializer', $mySerializer);

        $definition = new Definition();
        $definition->setSynthetic(true);
        $builder->setDefinition('MySerializer', $definition);

        $builder->compile();

        $anonymizer = $builder->get(Anonymizer::class);
        list($normalizer, $denormalizer) = $this->resolveSerializerComponents($anonymizer);

        self::assertNotNull($normalizer);
        self::assertNotSame($mySerializer, $normalizer);
        self::assertNotSame($serializer, $normalizer);

        self::assertNotNull($denormalizer);
        self::assertNotSame($mySerializer, $denormalizer);
        self::assertNotSame($serializer, $denormalizer);
    }

    public static function provideConfigFiles(): Generator
    {
        $finder = new Finder();
        $finder->in(sprintf('%s/config', FIXTURES_ROOT));

        foreach ($finder as $file) {
            if (str_starts_with($file->getFilename(), 'with')) {
                continue;
            }

            yield [$file->getRealPath()];
        }
    }

    /**
     * @return array{0:mixed,1:mixed}
     */
    private function resolveSerializerComponents(Anonymizer $anonymizer): array
    {
        $anonymizerReflection = new ReflectionClass($anonymizer);

        /** @var DefaultDataProcessor $dataProcessor */
        $dataProcessor = $anonymizerReflection->getProperty('dataProcessor')->getValue($anonymizer);
        $dataProcessorReflection = new ReflectionClass($dataProcessor);

        /** @var DefaultDataEncodingProvider $dataEncodingProvider */
        $dataEncodingProvider = $dataProcessorReflection->getProperty('dataEncodingProvider')->getValue($dataProcessor);
        $dataEncodingProviderReflection = new ReflectionClass($dataEncodingProvider);

        $normalizer = $dataEncodingProviderReflection->getProperty('normalizer')->getValue($dataEncodingProvider);
        $denormalizer = $dataEncodingProviderReflection->getProperty('denormalizer')->getValue($dataEncodingProvider);

        return [$normalizer, $denormalizer];
    }
}
