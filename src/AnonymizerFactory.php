<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer;

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Serializer\SerializerBuilder;
use Symfony\Component\Serializer\Serializer;

final readonly class AnonymizerFactory
{
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
     * @param array{
     *      enabled: bool,
     *      encoders: array{
     *        json: bool,
     *        xml: bool,
     *        yaml: bool,
     *      },
     *      naming_schemas: array{
     *        method_names: string,
     *        variable_names: string,
     *        with_isser_functions: bool,
     *      },
     *      resolvers: array{
     *        attributes: bool,
     *        phpdocs: bool,
     *        reflection: bool,
     *      },
     *    } $serializerOptions
     */
    public function __construct(
        private array $dataOptions,
        private array $parserOptions,
        private array $rules,
        private array $serializerOptions,
        private DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
    ) {
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

        if ($this->serializerOptions['enabled']) {
            if (!$this->dependencyChecker->libraryIsInstalled('symfony/serializer')) {
                throw new MissingPlatformRequirementsException('The symfony/serializer package is required for this encoder');
            }

            $serializer = $this->createSerializer();

            $builder
                ->withNormalizer($serializer)
                ->withDenormalizer($serializer);
        }

        $anonymizer = $builder->build();

        foreach ($this->rules as $ruleName => $ruleConfig) {
            $anonymizer->registerRuleSet(
                name: $ruleName,
                definitions: $ruleConfig['nodes'],
                defaultDataAccess: $ruleConfig['default_access'] ?? 'array',
            );
        }

        return $anonymizer;
    }

    private function createSerializer(): Serializer
    {
        $builder = (new SerializerBuilder())
            ->withVariableNameSchema($this->serializerOptions['naming_schemas']['variable_names'])
            ->withMethodNameSchema($this->serializerOptions['naming_schemas']['method_names']);

        if ($this->serializerOptions['naming_schemas']['with_isser_functions']) {
            $builder->withIsserPrefixSupport();
        }

        if ($this->serializerOptions['encoders']['json']) {
            $builder->withJsonEncoder();
        }

        if ($this->serializerOptions['encoders']['xml']) {
            $builder->withXmlEncoder();
        }

        if ($this->serializerOptions['encoders']['yaml']) {
            $builder->withYamlEncoder();
        }

        if ($this->serializerOptions['resolvers']['attributes']) {
            $builder->withAttributeResolver();
        }

        if ($this->serializerOptions['resolvers']['phpdocs']) {
            $builder->withPhpDocsResolver();
        }

        if ($this->serializerOptions['resolvers']['reflection']) {
            $builder->withReflectionResolver();
        }

        return $builder->build();
    }
}
