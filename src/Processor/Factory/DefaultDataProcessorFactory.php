<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Processor\Factory;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\Enum\DataProcessor;
use PhpAnonymizer\Anonymizer\Exception\DataProcessorExistsException;
use PhpAnonymizer\Anonymizer\Exception\InvalidDataProcessorDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\UnknownDataProcessorException;
use PhpAnonymizer\Anonymizer\Processor\DataProcessorInterface;
use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use function in_array;
use function is_callable;
use function sprintf;

class DefaultDataProcessorFactory implements DataProcessorFactoryInterface
{
    private const DATA_PROCESSORS = [
        DataProcessor::DEFAULT->value,
    ];

    /** @var array<string,DataProcessorInterface> */
    private array $dataProcessors = [];

    /** @var array<string,callable> */
    private array $customDataProcessors = [];

    /**
     * @param callable|DataProcessorInterface $definition
     */
    public function registerCustomDataProcessor(string $name, mixed $definition): void
    {
        if (in_array($name, self::DATA_PROCESSORS, true) || in_array($name, $this->customDataProcessors, true)) {
            throw new DataProcessorExistsException(sprintf('Cannot override existing data processor: "%s"', $name));
        }

        if ($definition instanceof DataProcessorInterface) {
            $this->customDataProcessors[$name] = static fn () => $definition;

            return;
        }

        if (!is_callable($definition)) {
            throw new InvalidDataProcessorDefinitionException('Data processor definition must be a callable');
        }

        $this->customDataProcessors[$name] = $definition;
    }

    public function getDataProcessor(
        ?string $type,
        ?DataAccessProviderInterface $dataAccessProvider = null,
        ?DataGenerationProviderInterface $dataGenerationProvider = null,
        ?DataEncodingProviderInterface $dataEncodingProvider = null,
    ): DataProcessorInterface {
        if ($type === null) {
            throw new InvalidDataProcessorDefinitionException('Data processor type must be provided');
        }

        if (!isset($this->dataProcessors[$type])) {
            $this->dataProcessors[$type] = $this->resolveDataProcessor($type, $dataAccessProvider, $dataGenerationProvider, $dataEncodingProvider);
        }

        return $this->dataProcessors[$type];
    }

    private function resolveDataProcessor(
        string $type,
        ?DataAccessProviderInterface $dataAccessProvider,
        ?DataGenerationProviderInterface $dataGenerationProvider,
        ?DataEncodingProviderInterface $dataEncodingProvider = null,
    ): DataProcessorInterface {
        if (isset($this->customDataProcessors[$type])) {
            $dataProcessor = $this->customDataProcessors[$type]($dataAccessProvider, $dataGenerationProvider);
            if (!$dataProcessor instanceof DataProcessorInterface) {
                throw new InvalidDataProcessorDefinitionException('Custom data processor must implement DataProcessorInterface');
            }

            return $dataProcessor;
        }

        return match ($type) {
            DataProcessor::DEFAULT->value => new DefaultDataProcessor(
                $dataAccessProvider ?? throw new InvalidDataProcessorDefinitionException('Data access provider is required'),
                $dataGenerationProvider ?? throw new InvalidDataProcessorDefinitionException('Data generation provider is required'),
                $dataEncodingProvider ?? throw new InvalidDataProcessorDefinitionException('Data encoding provider is required'),
            ),
            default => throw new UnknownDataProcessorException(sprintf('Unknown data processor: "%s"', $type)),
        };
    }
}
