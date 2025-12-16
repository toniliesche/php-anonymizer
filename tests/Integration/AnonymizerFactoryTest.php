<?php

declare(strict_types=1);

namespace Integration;

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
        $anonymizerReflection = new ReflectionClass($anonymizer);

        /** @var DefaultDataProcessor $dataProcessor */
        $dataProcessor = $anonymizerReflection->getProperty('dataProcessor')->getValue($anonymizer);
        $dataProcessorReflection = new ReflectionClass($dataProcessor);

        /** @var DefaultDataEncodingProvider $dataEncodingProvider */
        $dataEncodingProvider = $dataProcessorReflection->getProperty('dataEncodingProvider')->getValue($dataProcessor);
        $dataEncodingProviderReflection = new ReflectionClass($dataEncodingProvider);

        $normalizer = $dataEncodingProviderReflection->getProperty('normalizer')->getValue($dataEncodingProvider);
        $denormalizer = $dataEncodingProviderReflection->getProperty('denormalizer')->getValue($dataEncodingProvider);

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
        $definition = new Definition(SerializerInterface::class);
        $definition->setPublic(true);
        $builder->setDefinition(SerializerInterface::class, $definition);
        $builder->set(SerializerInterface::class, $serializer);

        $builder->compile();

        $anonymizer = $builder->get(Anonymizer::class);
        $anonymizerReflection = new ReflectionClass($anonymizer);

        /** @var DefaultDataProcessor $dataProcessor */
        $dataProcessor = $anonymizerReflection->getProperty('dataProcessor')->getValue($anonymizer);
        $dataProcessorReflection = new ReflectionClass($dataProcessor);

        /** @var DefaultDataEncodingProvider $dataEncodingProvider */
        $dataEncodingProvider = $dataProcessorReflection->getProperty('dataEncodingProvider')->getValue($dataProcessor);
        $dataEncodingProviderReflection = new ReflectionClass($dataEncodingProvider);

        $normalizer = $dataEncodingProviderReflection->getProperty('normalizer')->getValue($dataEncodingProvider);
        $denormalizer = $dataEncodingProviderReflection->getProperty('denormalizer')->getValue($dataEncodingProvider);

        self::assertSame($serializer, $normalizer);
        self::assertSame($serializer, $denormalizer);
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

        $builder->compile();

        $anonymizer = $builder->get(Anonymizer::class);
        $anonymizerReflection = new ReflectionClass($anonymizer);

        /** @var DefaultDataProcessor $dataProcessor */
        $dataProcessor = $anonymizerReflection->getProperty('dataProcessor')->getValue($anonymizer);
        $dataProcessorReflection = new ReflectionClass($dataProcessor);

        /** @var DefaultDataEncodingProvider $dataEncodingProvider */
        $dataEncodingProvider = $dataProcessorReflection->getProperty('dataEncodingProvider')->getValue($dataProcessor);
        $dataEncodingProviderReflection = new ReflectionClass($dataEncodingProvider);

        $normalizer = $dataEncodingProviderReflection->getProperty('normalizer')->getValue($dataEncodingProvider);
        $denormalizer = $dataEncodingProviderReflection->getProperty('denormalizer')->getValue($dataEncodingProvider);

        self::assertSame($serializer, $normalizer);
        self::assertSame($serializer, $denormalizer);
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
}
