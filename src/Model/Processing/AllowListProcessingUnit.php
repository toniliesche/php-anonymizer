<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model\Processing;

use PhpAnonymizer\Anonymizer\DataAccess\DataAccessInterface;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\DataEncodingException;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Model\Data\Node as DataNode;
use PhpAnonymizer\Anonymizer\Model\Rule\Node as RuleNode;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSet;
use PhpAnonymizer\Anonymizer\Model\Rule\RuleSetProviderInterface;
use PhpAnonymizer\Anonymizer\Model\Rule\Tree as RuleTree;
use PhpAnonymizer\Anonymizer\Model\TempStorage;
use function array_slice;
use function is_array;
use function is_string;

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

        $dataAccess = $this->resolveDataAccess();
        $dataTree = $dataAccess->parseDataTree($data);

        foreach ($dataTree->childNodes as $node) {
            $this->processNode([], $data, $node, $this->ruleSet->tree, $dataAccess);
        }

        return $encoder->encode($data, $this->tempStorage);
    }

    /**
     * @param string[] $path
     */
    private function getAnonymizedValue(array $path, mixed $value, ?string $valueType): string
    {
        return $this->dataGenerationProvider->provideDataGenerator($value, $valueType)->generate($path, $value, $valueType);
    }

    private function resolveDataAccess(): DataAccessInterface
    {
        $dataAccess = $this->overrideDataAccess ?? $this->ruleSet->defaultDataAccess;
        $dataAccess = $dataAccess === DataAccess::DEFAULT->value ? DataAccess::AUTODETECT->value : $dataAccess;

        return $this->dataAccessProvider->provideDataAccess($dataAccess);
    }

    /**
     * @param string[] $path
     */
    private function processNode(
        array $path,
        mixed &$data,
        DataNode $node,
        RuleTree|RuleNode $ruleNode,
        DataAccessInterface $dataAccess,
    ): void {
        $path[] = $node->name;

        if (!$dataAccess->hasChild($path, $data, $node->name)) {
            return;
        }

        $value = $dataAccess->getChild($path, $data, $node->name);
        $matchingRuleNode = $ruleNode->hasChildNode($node->name) ? $ruleNode->getChildNode($node->name) : null;
        $nextRuleNode = $matchingRuleNode?->nodeType === NodeType::NODE ? $matchingRuleNode : new RuleTree();

        if ($node->nodeType === NodeType::LEAF) {
            if ($this->shouldKeepLeafValue($matchingRuleNode, $data, $dataAccess, $path)) {
                if ($matchingRuleNode?->containsNestedData()) {
                    $nestedRuleSet = $this->ruleSetProvider->getRuleSet($matchingRuleNode->nestedRule);
                    $nestedUnit = new AllowListProcessingUnit(
                        $this->dataGenerationProvider,
                        $this->dataAccessProvider,
                        $this->dataEncodingProvider,
                        $this->ruleSetProvider,
                        $nestedRuleSet,
                        $value,
                        new TempStorage(),
                    );

                    $value = $nestedUnit->process($matchingRuleNode->nestedType);
                    $dataAccess->setChildValue($path, $data, $node->name, $value);
                }

                return;
            }

            if ($node->isList && is_array($value)) {
                foreach ($value as $key => $item) {
                    if (!is_string($item)) {
                        continue;
                    }

                    $value[$key] = $this->getAnonymizedValue($path, $item, null);
                }

                $dataAccess->setChildValue($path, $data, $node->name, $value);

                return;
            }

            if (is_string($value)) {
                $dataAccess->setChildValue(
                    $path,
                    $data,
                    $node->name,
                    $this->getAnonymizedValue($path, $value, null),
                );
            }

            return;
        }

        if ($node->isList) {
            if (!is_array($value)) {
                throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
            }

            foreach (array_keys($value) as $key) {
                foreach ($node->childNodes as $childNode) {
                    $this->processNode($path, $value[$key], $childNode, $nextRuleNode, $dataAccess);
                }
            }

            $dataAccess->setChildValue($path, $data, $node->name, $value);

            return;
        }

        foreach ($node->childNodes as $childNode) {
            $this->processNode($path, $value, $childNode, $nextRuleNode, $dataAccess);
        }

        $dataAccess->setChildValue($path, $data, $node->name, $value);
    }

    /**
     * @param string[] $path
     */
    private function shouldKeepLeafValue(
        ?RuleNode $ruleNode,
        mixed $parent,
        DataAccessInterface $dataAccess,
        array $path,
    ): bool {
        if (!$ruleNode instanceof RuleNode) {
            return false;
        }

        if (!$ruleNode->hasFilterRule()) {
            return true;
        }

        try {
            $filterValue = $dataAccess->getChild($path, $parent, $ruleNode->filterField);
        } catch (FieldDoesNotExistException|InvalidObjectTypeException) {
            return false;
        }

        return $filterValue === $ruleNode->filterValue;
    }
}
