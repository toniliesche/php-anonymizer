<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Processor;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\Model\Processing\AllowListProcessingUnit;
use PhpAnonymizer\Anonymizer\Model\Processing\DenyListProcessingUnit;
use PhpAnonymizer\Anonymizer\Model\Processing\ProcessingUnitInterface;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSet;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSetProvider;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSetProviderInterface;

final readonly class DefaultDataProcessor implements DataProcessorInterface
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
        $ruleSet = $this->ruleSetProvider->getRuleSet($ruleSetName);

        $processingUnit = $ruleSet->denyList
            ? $this->getDenyListProcessingUnit($ruleSet, $data)
            : $this->getDenyAllowProcessingUnit($ruleSet, $data);

        return $processingUnit->process($encoding);
    }

    public function getRuleSetProvider(): RuleSetProviderInterface
    {
        return $this->ruleSetProvider;
    }

    private function getDenyListProcessingUnit(RuleSet $ruleSet, mixed $data): ProcessingUnitInterface
    {
        return new DenyListProcessingUnit(
            dataGenerationProvider: $this->dataGenerationProvider,
            dataAccessProvider: $this->dataAccessProvider,
            dataEncodingProvider: $this->dataEncodingProvider,
            ruleSetProvider: $this->ruleSetProvider,
            ruleSet: $ruleSet,
            data: $data,
        );
    }

    private function getDenyAllowProcessingUnit(RuleSet $ruleSet, mixed $data): ProcessingUnitInterface
    {
        return new AllowListProcessingUnit(
            dataGenerationProvider: $this->dataGenerationProvider,
            dataAccessProvider: $this->dataAccessProvider,
            dataEncodingProvider: $this->dataEncodingProvider,
            ruleSetProvider: $this->ruleSetProvider,
            ruleSet: $ruleSet,
            data: $data,
        );
    }
}
