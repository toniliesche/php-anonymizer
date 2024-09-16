<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataGeneration\Provider;

use Faker\Factory;
use Faker\Generator;
use PhpAnonymizer\Anonymizer\DataGeneration\FakerAwareStringGenerator;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Exception\UnsupportedDataTypeException;
use PhpAnonymizer\Anonymizer\Test\Helper\DataGeneration\RandomIntGenerator;
use PHPUnit\Framework\TestCase;
use stdClass;

class DefaultDataGenerationProviderTest extends TestCase
{
    public function testWillFailOnInitializationWithInvalidDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DefaultDataGeneratorProvider(
            /** @phpstan-ignore-next-line  */
            [
                'string',
            ],
        );
    }

    public function testWillFailOnInjectingFakerOnMissingFakerLibrary(): void
    {
        $dependencyChecker = $this->createMock(DependencyCheckerInterface::class);
        $dependencyChecker
            ->method('libraryIsInstalled')
            ->willReturn(false);

        $provider = new DefaultDataGeneratorProvider(
            [],
            $dependencyChecker,
        );

        $this->expectException(MissingPlatformRequirementsException::class);

        /** @phpstan-ignore-next-line  */
        $provider->injectFaker(new stdClass());
    }

    public function testWillFailOnInjectingFakerWhenInvalidArgumentIsPassed(): void
    {
        $provider = new DefaultDataGeneratorProvider([]);

        $this->expectException(InvalidArgumentException::class);

        /** @phpstan-ignore-next-line  */
        $provider->injectFaker(new stdClass());
    }

    public function testCanProvideDefaultDataGenerator(): void
    {
        $provider = new DefaultDataGeneratorProvider(
            [
                new StarMaskedStringGenerator(),
            ],
        );

        $dataGenerator = $provider->provideDataGenerator('Hello World', null);
        $this->assertInstanceOf(StarMaskedStringGenerator::class, $dataGenerator);
    }

    public function testWillFailOnProvideUnknownDataGenerator(): void
    {
        $provider = new DefaultDataGeneratorProvider(
            [
                new StarMaskedStringGenerator(),
            ],
        );

        $this->expectException(UnsupportedDataTypeException::class);
        $provider->provideDataGenerator(new stdClass(), null);
    }

    public function testCanRegisterAndProvideCustomDataGenerator(): void
    {
        $provider = new DefaultDataGeneratorProvider(
            [
                new StarMaskedStringGenerator(),
            ],
        );
        $provider->registerCustomDataGenerator(new RandomIntGenerator());

        $resolvedDataGenerator = $provider->provideDataGenerator(42, null);
        $this->assertInstanceOf(RandomIntGenerator::class, $resolvedDataGenerator);
    }

    public function testCanRegisterAndProvideCustomDataGeneratorScenario2(): void
    {
        $dataGenerator = new RandomIntGenerator();

        $provider = new DefaultDataGeneratorProvider(
            [
                new StarMaskedStringGenerator(),
            ],
        );
        $provider->registerCustomDataGenerator($dataGenerator);

        $resolvedDataGenerator = $provider->provideDataGenerator(1, null);
        $this->assertSame($dataGenerator, $resolvedDataGenerator);
    }

    public function testCanRegisterCustomDataGeneratorAndInjectFaker(): void
    {
        $dataGenerator = $this->createMock(FakerAwareStringGenerator::class);
        $dataGenerator->expects($this->once())
            ->method('setFaker');

        $provider = new DefaultDataGeneratorProvider(
            [
                new StarMaskedStringGenerator(),
            ],
        );
        $provider->registerCustomDataGenerator($dataGenerator);
        $provider->injectFaker($this->getFaker());
    }

    public function testCanInjectFakerAndProvideCustomDataGenerator(): void
    {
        $dataGenerator = $this->createMock(FakerAwareStringGenerator::class);
        $dataGenerator->expects($this->once())
            ->method('setFaker');

        $provider = new DefaultDataGeneratorProvider(
            [
                new FakerAwareStringGenerator(),
            ],
        );
        $provider->injectFaker($this->getFaker());
        $provider->registerCustomDataGenerator($dataGenerator);
    }

    public function testCanSetSeedOnFaker(): void
    {
        $provider = new DefaultDataGeneratorProvider(
            [
                new StarMaskedStringGenerator(),
            ],
        );

        $faker = $this->createMock(Generator::class);
        $faker->expects($this->once())
            ->method('seed');

        $provider->injectFaker($faker);
        $provider->setSeed('secret');
    }

    private function getFaker(): Generator
    {
        return Factory::create();
    }
}
