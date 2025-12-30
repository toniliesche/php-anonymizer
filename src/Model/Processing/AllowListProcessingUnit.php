<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model\Processing;

use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSet;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSetProviderInterface;
use PhpAnonymizer\Anonymizer\Model\TempStorage;

final class AllowListProcessingUnit implements ProcessingUnitInterface
{
    private ?string $overrideDataAccess = null;

    public function __construct(
        private readonly DataGenerationProviderInterface $dataGenerationProvider,
        private readonly DataAccessProviderInterface $dataAccessProvider,
        private readonly ?DataEncodingProviderInterface $dataEncodingProvider,
        private readonly RuleSetProviderInterface $ruleSetProvider,
        private readonly RuleSet $ruleSet,
        private readonly mixed $data,
        private readonly TempStorage $tempStorage = new TempStorage(),
    ) {
    }

    public function process(?string $encoding = null): mixed
    {
        $encoder = $this->dataEncodingProvider->provideEncoder($encoding);
        if (!$encoder->supports($this->data)) {
            return throw new DataEncodingException(
                sprintf('The provided data is not supported by the encoder %s', $encoding),
            );
        }

        $this->overrideDataAccess = $encoder->getOverrideDataAccess();
        $data = $encoder->decode($this->data, $this->tempStorage);

        return $encoder->encode($data, $this->tempStorage);
    }

    /**
     * @param string[] $path
     */
    private function getAnonymizedValue(array $path, mixed $value, ?string $valueType): string
    {
        return $this->dataGenerationProvider->provideDataGenerator($value, $valueType)->generate($path, $value, $valueType);
    }
}
