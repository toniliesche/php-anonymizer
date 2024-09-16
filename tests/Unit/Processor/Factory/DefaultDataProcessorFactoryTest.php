<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\Processor\Factory;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\Enum\DataProcessor;
use PhpAnonymizer\Anonymizer\Exception\DataProcessorExistsException;
use PhpAnonymizer\Anonymizer\Exception\InvalidDataProcessorDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\UnknownDataProcessorException;
use PhpAnonymizer\Anonymizer\Processor\DataProcessorInterface;
use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PhpAnonymizer\Anonymizer\Processor\Factory\DefaultDataProcessorFactory;
use PHPUnit\Framework\TestCase;
use stdClass;

class DefaultDataProcessorFactoryTest extends TestCase
{
    public function testCanProvideDefaultDataProcessor(): void
    {
        $factory = new DefaultDataProcessorFactory();
        $processor = $factory->getDataProcessor(
            DataProcessor::DEFAULT->value,
            new DefaultDataAccessProvider(),
            new DefaultDataGeneratorProvider([]),
            new DefaultDataEncodingProvider(),
        );

        $this->assertInstanceOf(DefaultDataProcessor::class, $processor);
    }

    public function testCanProvideNull(): void
    {
        $factory = new DefaultDataProcessorFactory();

        $this->expectException(InvalidDataProcessorDefinitionException::class);
        $factory->getDataProcessor(
            type: null,
            dataEncodingProvider: new DefaultDataEncodingProvider(),
        );
    }

    public function testWillFailOnProvideUnknownDataProcessor(): void
    {
        $factory = new DefaultDataProcessorFactory();
        $this->expectException(UnknownDataProcessorException::class);

        $factory->getDataProcessor(
            type: 'unknown',
            dataEncodingProvider: new DefaultDataEncodingProvider(),
        );
    }

    public function testCanRegisterAndProvideCustomDataProcessorWithCallable(): void
    {
        $callable = fn () => $this->createMock(DataProcessorInterface::class);
        $factory = new DefaultDataProcessorFactory();
        $factory->registerCustomDataProcessor('custom', $callable);

        $processor = $factory->getDataProcessor(
            type: 'custom',
            dataEncodingProvider: new DefaultDataEncodingProvider(),
        );
        $this->assertInstanceOf(DataProcessorInterface::class, $processor);
    }

    public function testCanRegisterAndProvideCustomDataProcessorWithInstance(): void
    {
        $processor = $this->createMock(DataProcessorInterface::class);
        $factory = new DefaultDataProcessorFactory();
        $factory->registerCustomDataProcessor('custom', $processor);

        $provider = $factory->getDataProcessor(
            type: 'custom',
            dataEncodingProvider: new DefaultDataEncodingProvider(),
        );
        $this->assertSame($processor, $provider);
    }

    public function testWillFailOnRegisterCustomDataProcessorOnNameConflict(): void
    {
        $callable = fn () => $this->createMock(DataProcessorInterface::class);
        $factory = new DefaultDataProcessorFactory();

        $this->expectException(DataProcessorExistsException::class);
        $factory->registerCustomDataProcessor(DataProcessor::DEFAULT->value, $callable);
    }

    public function testWillFailOnRegisterCustomDataProcessorWithNonCallableDefinition(): void
    {
        $factory = new DefaultDataProcessorFactory();

        $this->expectException(InvalidDataProcessorDefinitionException::class);

        /** @phpstan-ignore-next-line */
        $factory->registerCustomDataProcessor('custom', 'not a callable');
    }

    public function testWillFailOnRegisterAndProvideCustomDataProcessorWhenNotImplementingInterface(): void
    {
        $callable = fn () => new stdClass();
        $factory = new DefaultDataProcessorFactory();

        $this->expectException(InvalidDataProcessorDefinitionException::class);
        $factory->registerCustomDataProcessor('custom', $callable);
        $factory->getDataProcessor('custom');
    }
}
