<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer;

use PhpAnonymizer\Anonymizer\Exception\AnonymizerFactoryException;
use PhpAnonymizer\Anonymizer\Exception\RuleDefinitionException;
use PhpAnonymizer\Anonymizer\RuleProvider\RuleProviderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class AnonymizerFactory
{
    // @phpstan-ignore-next-line
    private SerializerInterface&NormalizerInterface&DenormalizerInterface $serializer;

    /**
     * @param array{
     *      access_provider: string,
     *      faker: bool,
     *      faker_seed: ?string,
     *      generation_provider: string,
     *      processor_type: string,
     *    } $dataOptions
     * @param array{
     *      rule_set_parser: string,
     *      node_parser: string,
     *      node_mapper: string,
     *    } $parserOptions
     * @param array<string, array{
     *      nodes: array<mixed>,
     *    }> $rules
     * @param iterable<RuleProviderInterface> $ruleProviders
     */
    public function __construct(
        private array $dataOptions,
        private array $parserOptions,
        private array $rules,
        private iterable $ruleProviders,
        ?SerializerInterface $serializer = null,
    ) {
        if (($set = $this->validateSerializer($serializer)) !== null) {
            $this->serializer = $set;
        }
    }

    public function create(): Anonymizer
    {
        $builder = (new AnonymizerBuilder())
            // $dataOptions
            ->withDataAccessProviderType($this->dataOptions['access_provider'])
            ->withDataGenerationProviderType($this->dataOptions['generation_provider'])
            ->withDataProcessorType($this->dataOptions['processor_type'])
            ->withFaker($this->dataOptions['faker'])
            ->withFakerSeed($this->dataOptions['faker_seed'])
            // $parserOptions
            ->withRuleSetParserType($this->parserOptions['rule_set_parser'])
            ->withNodeParserType($this->parserOptions['node_parser'])
            ->withNodeMapperType($this->parserOptions['node_mapper']);

        if (isset($this->serializer)) {
            $builder
                ->withNormalizer($this->serializer)
                ->withDenormalizer($this->serializer);
        }

        $anonymizer = $builder->build();

        foreach ($this->rules as $ruleName => $ruleConfig) {
            $anonymizer->registerRuleSet(
                name: $ruleName,
                definitions: $ruleConfig['nodes'],
                defaultDataAccess: $ruleConfig['default_access'] ?? 'array',
            );
        }

        foreach ($this->ruleProviders as $ruleProvider) {
            foreach ($ruleProvider->provideRules() as $ruleName => $rule) {
                $anonymizer->registerParsedRuleSet($ruleName, $rule);
            }
        }

        return $anonymizer;
    }

    private function validateSerializer(?SerializerInterface $serializer): (SerializerInterface&NormalizerInterface&DenormalizerInterface)|null
    {
        if ($serializer === null) {
            return null;
        }

        if (!$serializer instanceof NormalizerInterface) {
            throw new AnonymizerFactoryException('Serializer must implement NormalizerInterface');
        }

        if (!$serializer instanceof DenormalizerInterface) {
            throw new RuleDefinitionException('Serializer must implement DenormalizerInterface');
        }

        /** @var DenormalizerInterface&NormalizerInterface&SerializerInterface $serializer */
        return $serializer;
    }
}
