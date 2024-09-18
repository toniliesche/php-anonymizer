<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Processor;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\Model\ProcessingUnit;
use PhpAnonymizer\Anonymizer\Model\RuleSet;

readonly class DefaultDataProcessor implements DataProcessorInterface
{
    public function __construct(
        private DataAccessProviderInterface $dataAccessProvider,
        private DataGenerationProviderInterface $dataGenerationProvider,
        private DataEncodingProviderInterface $dataEncodingProvider,
    ) {
    }

    public function process(mixed $data, RuleSet $ruleSet, ?string $encoding = null): mixed
    {
        return (new ProcessingUnit(
            dataGenerationProvider: $this->dataGenerationProvider,
            dataAccessProvider: $this->dataAccessProvider,
            dataEncodingProvider: $this->dataEncodingProvider,
            ruleSet: $ruleSet,
            data: $data,
        ))->process($encoding);
    }
}
