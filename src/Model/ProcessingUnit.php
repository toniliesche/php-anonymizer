<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use function sprintf;

class ProcessingUnit
{
    private ?string $overrideDataAccess;

    public function __construct(
        private readonly DataGenerationProviderInterface $dataGenerationProvider,
        private readonly DataAccessProviderInterface $dataAccessProvider,
        private readonly ?DataEncodingProviderInterface $dataEncodingProvider,
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

        foreach ($this->ruleSet->tree->childNodes as $rule) {
            $data = $this->processNode([], $data, $rule);
        }

        return $encoder->encode($data, $this->tempStorage);
    }

    /**
     * @param string[] $path
     */
    private function processNode(array $path, mixed &$data, Node $rule): mixed
    {
        $path[] = $rule->name;

        $dataAccess = $this->getDataAccess($rule->dataAccess);
        if (!$dataAccess->hasChild($path, $data, $rule->name)) {
            return $data;
        }

        $value = $dataAccess->getChild($path, $data, $rule->name);
        $value = $this->processValue($path, $rule, $value);

        $dataAccess->setChildValue($path, $data, $rule->name, $value);

        return $data;
    }

    /**
     * @param string[] $path
     */
    private function processValue(array $path, Node $rule, mixed $value): mixed
    {
        if ($rule->isArray) {
            return $this->processListValue($path, $rule, $value);
        }

        return $this->processSingleValue($path, $rule, $value);
    }

    private function getDataAccess(string $dataAccess): DataAccessInterface
    {
        if ($this->overrideDataAccess !== null) {
            return $this->dataAccessProvider->provideDataAccess($this->overrideDataAccess);
        }

        if ($dataAccess === DataAccess::DEFAULT->value) {
            return $this->getDataAccess($this->ruleSet->defaultDataAccess);
        }

        return $this->dataAccessProvider->provideDataAccess($dataAccess);
    }

    /**
     * @param string[] $path
     */
    private function processSingleValue(array $path, Node $rule, mixed $value): mixed
    {
        if ($rule->nodeType === NodeType::LEAF) {
            return $this->getAnonymizedValue($path, $value, $rule->valueType);
        }

        foreach ($rule->childNodes as $childRule) {
            $value = $this->processNode($path, $value, $childRule);
        }

        return $value;
    }

    /**
     * @param string[] $path
     */
    private function processListValue(array $path, Node $rule, mixed $value): mixed
    {
        foreach ($value as $key => $item) {
            $value[$key] = $this->processSingleValue($path, $rule, $item);
        }

        return $value;
    }

    /**
     * @param string[] $path
     */
    private function getAnonymizedValue(array $path, mixed $value, ?string $valueType): string
    {
        return $this->dataGenerationProvider->provideDataGenerator($value, $valueType)->generate($path, $value, $valueType);
    }
}
