<?php

declare(strict_types=1);

use Faker\Factory;
use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DefaultDataAccessProviderFactory;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DefaultDataGenerationProviderFactory;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataAccessProvider;
use PhpAnonymizer\Anonymizer\Enum\DataGenerationProvider;
use PhpAnonymizer\Anonymizer\Enum\NodeMapper;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use PhpAnonymizer\Anonymizer\Mapper\Node\DefaultNodeMapper;
use PhpAnonymizer\Anonymizer\Mapper\Node\Factory\DefaultNodeMapperFactory;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\Node\Factory\DefaultNodeParserFactory;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\ArrayRuleSetParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory\DefaultRuleSetParserFactory;
use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PhpAnonymizer\Anonymizer\Processor\Factory\DefaultDataProcessorFactory;
use PhpAnonymizer\Anonymizer\RuleLoader\ArrayRuleLoader;

require_once __DIR__ . '/../vendor/autoload.php';

// 05.02.01 Creating AnonymizerBuilder

/**
 * AnonymizerBuilder:
 * - takes optional argument $nodeParserFactory:
 *   - must implement PhpAnonymizer\Anonymizer\Parser\Node\Factory\NodeParserFactoryInterface
 *   - defaults to DefaultNodeParserFactory
 * - takes optional argument $ruleSetParserFactory:
 *   - must implement PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory\RuleSetParserFactoryInterface
 *   - defaults to DefaultRuleSetParserFactory
 * - takes optional argument $dataProcessorFactory:
 *   - must implement PhpAnonymizer\Anonymizer\Processor\Factory\DataProcessorFactoryInterface
 *   - defaults to DefaultDataProcessorFactory
 * - takes optional argument $dataAccessProviderFactory:
 *   - must implement PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DataAccessProviderFactoryInterface
 *   - defaults to DefaultDataAccessProviderFactory
 * - takes optional argument $dataGenerationProviderFactory:
 *   - must implement PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DataGenerationProviderFactoryInterface
 *   - defaults to DefaultDataGenerationProviderFactory
 * - takes optional argument $dataEncodingProvider:
 *   - must implement PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface
 *   - defaults to DefaultDataEncodingProvider
 * - takes optional argument $dependencyChecker:
 *   - must implement PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface
 *   - defaults to DefaultDependencyChecker
 *  - takes optional argument $nodeMapperFactory:
 *    - must implement PhpAnonymizer\Anonymizer\Mapper\Node\Factory\NodeMapperFactoryInterface
 *    - defaults to DefaultNodeMapperFactory
 */
$builder = new AnonymizerBuilder(
    nodeParserFactory: new DefaultNodeParserFactory(),
    ruleSetParserFactory: new DefaultRuleSetParserFactory(),
    dataProcessorFactory: new DefaultDataProcessorFactory(),
    dataAccessProviderFactory: new DefaultDataAccessProviderFactory(),
    dataGenerationProviderFactory: new DefaultDataGenerationProviderFactory(),
    dataEncodingProvider: new DefaultDataEncodingProvider(),
    nodeMapperFactory: new DefaultNodeMapperFactory(),
);

// 05.02.02 Setting Defaults

/**
 * Sets default values for the builder:
 * - nodeParserType: 'simple'
 * - nodeMapperType: 'default'
 * - ruleSetParserType: 'default'
 * - dataProcessorType: 'default'
 * - dataAccessProviderType: 'default'
 * - dataGenerationProviderType: 'default'
 * - faker: false
 */
$builder->withDefaults();

// 05.02.03 Setting NodeParser

/**
 * Two options to set node parser:
 * - via instance:
 *   - must implement PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface
 * - via type:
 *   - must be a string
 *   - will be resolved from node parser factory (set in (05.02.01)[#050201-creating-anonymizerbuilder])
 * - using one option will override the other
 */
$builder->withNodeParser(
    nodeParser: new ArrayNodeParser(),
);
$builder->withNodeParserType(
    nodeParserType: NodeParser::DEFAULT->value,
);

// 05.02.04 Setting NodeMapper

/**
 * Two options to set node mapper:
 * - via instance:
 *   - must implement PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface
 * - via type:
 *   - must be a string
 *   - will be resolved from node mapper factory (set in (05.02.01)[#050201-creating-anonymizerbuilder])
 *   - using one option will override the other
 */
$builder->withNodeMapper(
    nodeMapper: new DefaultNodeMapper(),
);
$builder->withNodeMapperType(
    nodeMapperType: NodeMapper::DEFAULT->value,
);

// 05.02.05 Setting DataAccessProvider

/**
 * Two options to set data access provider:
 * - via instance:
 *    - must implement `PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface`
 * - via type:
 *   - must be a string
 *   - will be resolved from data access provider factory (set in [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder))
 *   - using one option will override the other
 */
$builder->withDataAccessProvider(
    dataAccessProvider: new DefaultDataAccessProvider(),
);

$builder->withDataAccessProviderType(
    dataAccessProviderType: DataAccessProvider::DEFAULT->value,
);

// 05.02.06 Setting DataGenerationProvider

$dataGeneratorProvider = new DefaultDataGeneratorProvider(
    [
        new StarMaskedStringGenerator(),
    ],
);

$builder->withDataGenerationProvider(
    dataGenerator: $dataGeneratorProvider,
);

$builder->withDataGenerationProviderType(
    dataGeneratorType: DataGenerationProvider::DEFAULT->value,
);

// 05.02.07 Enabling Faker

/**
 * Enable or disable via bool value.
 */
$builder->withFaker(
    faker: true,
);

// 05.02.08 Setting Custom Faker

/**
 * Build custom Faker instance and inject into builder.
 */
$faker = Factory::create('de_DE');
$builder->withCustomFaker(
    faker: $faker,
);

// 05.02.09 Setting Faker Seed

/**
 * Set seed as string.
 */
$builder->withFakerSeed('my-faker-seed');

// 05.02.10 Setting Rule Loader

/**
 * Four options to set rules:
 * - via instance:
 *   - must implement PhpAnonymizer\Anonymizer\RuleLoader\RuleLoaderInterface
 * - via array directly:
 *   - must be string to existing file
 * - via json file path:
 *    - must be string to existing file
 *    - for the loader to work, the json php extension is required
 * - via yaml file path:
 *    - must be string to existing file
 *    - for the loader to work, the yaml php extension is required
 *
 * In any case the given rule format must match the configured ruleset parser's input format.
 */
$rules = [];

$ruleLoader = new ArrayRuleLoader(
    rules: $rules,
);
$builder->withRuleLoader(
    ruleLoader: $ruleLoader,
);

$builder->withRulesFromArray(
    rules: $rules,
);

$builder->withRulesFromJsonFile(
    filePath: 'rules.json',
);

$builder->withRulesFromYamlFile(
    filePath: 'rules.yml',
);

// 05.02.11 Setting RuleSetParser

/**
 * Two options to set rule set parser:
 * - via instance:
 *   - must implement PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface
 *   - will ignore dependencies set in builder
 * - via type:
 *   - must be a string
 *   - will be resolved from RuleSetParserFactory (set in (05.02.01)[#050201-creating-anonymizerbuilder])
 *   - will use NodeParser from (05.02.03)[#050203-setting-nodeparser]
 * - using one option will override the other
 */
$builder->withRuleSetParser(
    ruleSetParser: new ArrayRuleSetParser(),
);

$builder->withRuleSetParserType(
    ruleSetParserType: RuleSetParser::DEFAULT->value,
);

// 05.02.12 Setting DataProcessor

/**
 * Two options to set data processor:
 * - via instance:
 *   - must implement PhpAnonymizer\Anonymizer\Processor\DataProcessorInterface
 *   - will ignore dependencies set in builder
 * - via type:
 *   - must be a string
 *   - will be resolved from DataProcessorFactory (set in (05.02.01)[#050201-creating-anonymizerbuilder])
 *   - will use DataAccessProvider from (05.02.06)[#050206-setting-dataaccessprovider]
 *   - will use DataGenerationProvider from (05.02.07)[#050207-setting-datagenerationprovider]
 *   - will use DataEncodingProvider from (05.02.01)[#050201-creating-anonymizerbuilder]
 * - using one option will override the other
 */
$builder->withDataProcessor(
    new DefaultDataProcessor(
        dataAccessProvider: new DefaultDataAccessProvider(),
        dataGenerationProvider: new DefaultDataGeneratorProvider(
            [
                new StarMaskedStringGenerator(),
            ],
        ),
        dataEncodingProvider: new DefaultDataEncodingProvider(),
    ),
);
