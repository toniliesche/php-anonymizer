<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer;

use Faker\Factory;
use Faker\Generator;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DataAccessProviderFactoryInterface;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DefaultDataAccessProviderFactory;
use PhpAnonymizer\Anonymizer\DataEncoding\NormalizerAwareDataEncoderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\FakerAwareStringGenerator;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DataGenerationProviderFactoryInterface;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DefaultDataGenerationProviderFactory;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Enum\DataAccessProvider;
use PhpAnonymizer\Anonymizer\Enum\DataGenerationProvider;
use PhpAnonymizer\Anonymizer\Enum\DataProcessor;
use PhpAnonymizer\Anonymizer\Enum\NodeMapper;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use PhpAnonymizer\Anonymizer\Exception\AnonymizerBuilderException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;
use PhpAnonymizer\Anonymizer\Mapper\Node\Factory\DefaultNodeMapperFactory;
use PhpAnonymizer\Anonymizer\Mapper\Node\Factory\NodeMapperFactoryInterface;
use PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface;
use PhpAnonymizer\Anonymizer\Parser\Node\Factory\DefaultNodeParserFactory;
use PhpAnonymizer\Anonymizer\Parser\Node\Factory\NodeParserFactoryInterface;
use PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory\DefaultRuleSetParserFactory;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory\RuleSetParserFactoryInterface;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface;
use PhpAnonymizer\Anonymizer\Processor\DataProcessorInterface;
use PhpAnonymizer\Anonymizer\Processor\Factory\DataProcessorFactoryInterface;
use PhpAnonymizer\Anonymizer\Processor\Factory\DefaultDataProcessorFactory;
use PhpAnonymizer\Anonymizer\RuleLoader\ArrayRuleLoader;
use PhpAnonymizer\Anonymizer\RuleLoader\JsonFileRuleLoader;
use PhpAnonymizer\Anonymizer\RuleLoader\RuleLoaderInterface;
use PhpAnonymizer\Anonymizer\RuleLoader\YamlFileRuleLoader;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @codeCoverageIgnoreStart
 */
final class AnonymizerBuilder
{
    private string $nodeParserType;

    private ?NodeParserInterface $nodeParser = null;

    private string $nodeMapperType;

    private ?NodeMapperInterface $nodeMapper = null;

    private string $ruleSetParserType;

    private RuleSetParserInterface $ruleSetParser;

    private string $dataProcessorType;

    private DataProcessorInterface $dataProcessor;

    private string $dataAccessProviderType;

    private ?DataAccessProviderInterface $dataAccessProvider = null;

    private string $dataGeneratorType;

    private ?DataGenerationProviderInterface $dataGenerationProvider = null;

    private bool $withFaker = false;

    private Generator $faker;

    private ?string $fakerSeed = null;

    private ?RuleLoaderInterface $ruleLoader = null;

    public function __construct(
        private readonly NodeParserFactoryInterface $nodeParserFactory = new DefaultNodeParserFactory(),
        private readonly RuleSetParserFactoryInterface $ruleSetParserFactory = new DefaultRuleSetParserFactory(),
        private readonly DataProcessorFactoryInterface $dataProcessorFactory = new DefaultDataProcessorFactory(),
        private readonly DataAccessProviderFactoryInterface $dataAccessProviderFactory = new DefaultDataAccessProviderFactory(),
        private readonly DataGenerationProviderFactoryInterface $dataGenerationProviderFactory = new DefaultDataGenerationProviderFactory(),
        private readonly DataEncodingProviderInterface $dataEncodingProvider = new DefaultDataEncodingProvider(),
        private readonly DependencyCheckerInterface $dependencyChecker = new DefaultDependencyChecker(),
        private readonly NodeMapperFactoryInterface $nodeMapperFactory = new DefaultNodeMapperFactory(),
    ) {
    }

    public function withNormalizer(NormalizerInterface $normalizer): self
    {
        if (!$this->dataEncodingProvider instanceof NormalizerAwareDataEncoderInterface) {
            throw new AnonymizerBuilderException('DataEncodingProvider must implement NormalizerAwareDataEncoderInterface to set Normalizer.');
        }

        $this->dataEncodingProvider->setNormalizer($normalizer);

        return $this;
    }

    public function withDenormalizer(DenormalizerInterface $denormalizer): self
    {
        if (!$this->dataEncodingProvider instanceof NormalizerAwareDataEncoderInterface) {
            throw new AnonymizerBuilderException('DataEncodingProvider must implement NormalizerAwareDataEncoderInterface to set Denormalizer.');
        }

        $this->dataEncodingProvider->setDenormalizer($denormalizer);

        return $this;
    }

    public function withNodeMapperType(string $nodeMapperType): self
    {
        $this->nodeMapperType = $nodeMapperType;
        if (isset($this->nodeMapper)) {
            unset($this->nodeMapper);
        }

        return $this;
    }

    public function withNodeMapper(NodeMapperInterface $nodeMapper): self
    {
        $this->nodeMapper = $nodeMapper;
        if (isset($this->nodeMapperType)) {
            unset($this->nodeMapperType);
        }

        return $this;
    }

    public function withNodeParserType(string $nodeParserType): self
    {
        $this->nodeParserType = $nodeParserType;
        if (isset($this->nodeParser)) {
            unset($this->nodeParser);
        }

        return $this;
    }

    public function withNodeParser(NodeParserInterface $nodeParser): self
    {
        $this->nodeParser = $nodeParser;
        if (isset($this->nodeParserType)) {
            unset($this->nodeParserType);
        }

        return $this;
    }

    public function withRuleSetParserType(string $ruleSetParserType): self
    {
        $this->ruleSetParserType = $ruleSetParserType;
        if (isset($this->ruleSetParser)) {
            unset($this->ruleSetParser);
        }

        return $this;
    }

    public function withRuleSetParser(RuleSetParserInterface $ruleSetParser): self
    {
        $this->ruleSetParser = $ruleSetParser;
        if (isset($this->ruleSetParserType)) {
            unset($this->ruleSetParserType);
        }

        return $this;
    }

    public function withDataProcessorType(string $dataProcessorType): self
    {
        $this->dataProcessorType = $dataProcessorType;
        if (isset($this->dataProcessor)) {
            unset($this->dataProcessor);
        }

        return $this;
    }

    public function withDataProcessor(DataProcessorInterface $dataProcessor): self
    {
        $this->dataProcessor = $dataProcessor;
        if (isset($this->dataProcessorType)) {
            unset($this->dataProcessorType);
        }

        return $this;
    }

    public function withDataAccessProviderType(string $dataAccessProviderType): self
    {
        $this->dataAccessProviderType = $dataAccessProviderType;
        if (isset($this->dataAccessProvider)) {
            unset($this->dataAccessProvider);
        }

        return $this;
    }

    public function withDataAccessProvider(DataAccessProviderInterface $dataAccessProvider): self
    {
        $this->dataAccessProvider = $dataAccessProvider;
        if (isset($this->dataAccessProviderType)) {
            unset($this->dataAccessProviderType);
        }

        return $this;
    }

    public function withDataGenerationProviderType(string $dataGeneratorType): self
    {
        $this->dataGeneratorType = $dataGeneratorType;
        if (isset($this->dataGenerationProvider)) {
            unset($this->dataGenerationProvider);
        }

        return $this;
    }

    public function withDataGenerationProvider(DataGenerationProviderInterface $dataGenerator): self
    {
        $this->dataGenerationProvider = $dataGenerator;
        if (isset($this->dataGeneratorType)) {
            unset($this->dataGeneratorType);
        }

        return $this;
    }

    public function withCustomFaker(mixed $faker): self
    {
        if (!$this->dependencyChecker->libraryIsInstalled('fakerphp/faker')) {
            throw new MissingPlatformRequirementsException('Faker library is required to inject faker');
        }

        if (!$faker instanceof Generator) {
            throw new InvalidArgumentException('Faker object must be an instance of Faker Generator');
        }

        $this->withFaker = true;
        $this->faker = $faker;

        return $this;
    }

    public function withRuleLoader(?RuleLoaderInterface $ruleLoader): self
    {
        $this->ruleLoader = $ruleLoader;

        return $this;
    }

    public function withRulesFromJsonFile(string $filePath): self
    {
        if (!$this->dependencyChecker->extensionIsLoaded('json')) {
            throw new MissingPlatformRequirementsException('The json extension is required for this encoder');
        }

        $this->ruleLoader = new JsonFileRuleLoader($filePath);

        return $this;
    }

    public function withRulesFromYamlFile(string $filePath): self
    {
        if (!$this->dependencyChecker->extensionIsLoaded('yaml')) {
            throw new MissingPlatformRequirementsException('The yaml extension is required for this encoder');
        }

        $this->ruleLoader = new YamlFileRuleLoader($filePath);

        return $this;
    }

    /**
     * @param array<string, array<mixed>|string> $rules
     */
    public function withRulesFromArray(array $rules): self
    {
        $this->ruleLoader = new ArrayRuleLoader($rules);

        return $this;
    }

    public function withFaker(bool $faker): self
    {
        unset($this->faker);
        $this->withFaker = $faker;

        return $this;
    }

    public function withFakerSeed(?string $fakerSeed): self
    {
        $this->fakerSeed = $fakerSeed;

        return $this;
    }

    public function withDefaults(): self
    {
        $this->withNodeParserType(NodeParser::DEFAULT->value);
        $this->withNodeMapperType(NodeMapper::DEFAULT->value);
        $this->withRuleSetParserType(RuleSetParser::DEFAULT->value);
        $this->withDataProcessorType(DataProcessor::DEFAULT->value);
        $this->withDataAccessProviderType(DataAccessProvider::DEFAULT->value);
        $this->withDataGenerationProviderType(DataGenerationProvider::DEFAULT->value);
        $this->withFaker(false);
        $this->withRuleLoader(null);

        return $this;
    }

    public function build(): Anonymizer
    {
        if (!isset($this->ruleSetParser)) {
            $this->setupRuleSetParser();
        }

        if (!isset($this->dataProcessor)) {
            $this->setupDataProcessor();
        }

        if ($this->withFaker) {
            $this->setupFaker();
        }

        $anonymizer = new Anonymizer(
            ruleSetParser: $this->ruleSetParser,
            dataProcessor: $this->dataProcessor,
        );

        if ($this->ruleLoader instanceof RuleLoaderInterface) {
            foreach ($this->ruleLoader->loadRules() as $ruleName => $rule) {
                $anonymizer->registerRuleSet(
                    name: $ruleName,
                    definitions: $rule['nodes'],
                    defaultDataAccess: $rule['default_access'] ?? 'array',
                );
            }
        }

        return $anonymizer;
    }

    private function setupRuleSetParser(): void
    {
        if (!isset($this->nodeParser)) {
            $this->nodeParser = $this->nodeParserFactory->getNodeParser(
                type: $this->nodeParserType ?? null,
            );
        }

        if (!isset($this->nodeMapper)) {
            $this->nodeMapper = $this->nodeMapperFactory->getNodeMapper(
                type: $this->nodeMapperType,
            );
        }

        $this->ruleSetParser = $this->ruleSetParserFactory->getRuleSetParser(
            type: $this->ruleSetParserType ?? null,
            nodeParser: $this->nodeParser,
            nodeMapper: $this->nodeMapper,
        );
    }

    private function setupDataProcessor(): void
    {
        $this->dataAccessProvider = $this->dataAccessProviderFactory->getDataAccessProvider(
            type: $this->dataAccessProviderType ?? null,
        );
        $this->dataGenerationProvider = $this->dataGenerationProviderFactory->getDataGenerationProvider(
            type: $this->dataGeneratorType ?? null,
        );
        $this->dataProcessor = $this->dataProcessorFactory->getDataProcessor(
            type: $this->dataProcessorType ?? null,
            dataAccessProvider: $this->dataAccessProvider,
            dataGenerationProvider: $this->dataGenerationProvider,
            dataEncodingProvider: $this->dataEncodingProvider,
        );
    }

    private function setupFaker(): void
    {
        if (!$this->dependencyChecker->libraryIsInstalled('fakerphp/faker')) {
            throw new MissingPlatformRequirementsException('Faker library is required to inject faker');
        }

        if (!isset($this->dataGenerationProvider)) {
            throw new InvalidArgumentException('Data generation provider is required to inject faker');
        }

        $faker = $this->faker ?? Factory::create();
        $this->dataGenerationProvider->injectFaker($faker);

        if ($this->dataGenerationProvider instanceof DefaultDataGeneratorProvider) {
            $this->dataGenerationProvider->registerCustomDataGenerator(
                new FakerAwareStringGenerator(new StarMaskedStringGenerator()),
            );
        }

        if (isset($this->fakerSeed)) {
            $this->dataGenerationProvider->setSeed($this->fakerSeed);
        }
    }
}
