<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Processor;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\Model\ProcessingUnit;
use PhpAnonymizer\Anonymizer\Model\RuleSetProvider;
use PhpAnonymizer\Anonymizer\Model\RuleSetProviderInterface;

readonly class DefaultDataProcessor implements DataProcessorInterface
{
    public function __construct(
        private DataAccessProviderInterface $dataAccessProvider,
        private DataGenerationProviderInterface $dataGenerationProvider,
        private DataEncodingProviderInterface $dataEncodingProvider,
        private RuleSetProviderInterface $ruleSetProvider = new RuleSetProvider(),
    ) {
    }

    public function process(mixed $data, string $ruleSetName, ?string $encoding = null): mixed
    {
        return (new ProcessingUnit(
            dataGenerationProvider: $this->dataGenerationProvider,
            dataAccessProvider: $this->dataAccessProvider,
            dataEncodingProvider: $this->dataEncodingProvider,
            ruleSetProvider: $this->ruleSetProvider,
            ruleSet: $this->ruleSetProvider->getRuleSet($ruleSetName),
            data: $data,
        ))->process($encoding);
    }

    public function getRuleSetProvider(): RuleSetProviderInterface
    {
        return $this->ruleSetProvider;
    }
}
