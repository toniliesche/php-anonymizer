<?php

declare(strict_types=1);

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DefaultDataAccessProviderFactory;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DefaultDataGenerationProviderFactory;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexpParser;
use PhpAnonymizer\Anonymizer\Parser\Node\Factory\DefaultNodeParserFactory;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory\DefaultRuleSetParserFactory;
use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PhpAnonymizer\Anonymizer\Processor\Factory\DefaultDataProcessorFactory;

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
 */
$builder = new AnonymizerBuilder(
    nodeParserFactory: new DefaultNodeParserFactory(),
    ruleSetParserFactory: new DefaultRuleSetParserFactory(),
    dataProcessorFactory: new DefaultDataProcessorFactory(),
    dataAccessProviderFactory: new DefaultDataAccessProviderFactory(),
    dataGenerationProviderFactory: new DefaultDataGenerationProviderFactory(),
    dataEncodingProvider: new DefaultDataEncodingProvider(),
);

// 05.02.02 Setting Defaults

/**
 * Sets default values for the builder:
 * - nodeParserType: 'simple'
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
$builder->withNodeParser(new ComplexRegexpParser());
$builder->withNodeParserType('simple');

// 05.02.04 Setting RuleSetParser

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
$builder->withRuleSetParser(new DefaultRuleSetParser());
$builder->withRuleSetParserType('default');

// 05.02.05 Setting DataProcessor

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
