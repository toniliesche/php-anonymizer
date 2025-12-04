<?php

declare(strict_types=1);

use PhpAnonymizer\Anonymizer\Anonymizer;
use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DefaultDataAccessProviderFactory;
use PhpAnonymizer\Anonymizer\DataEncoding\JsonEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\FakerAwareStringGenerator;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DefaultDataGenerationProviderFactory;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;
use PhpAnonymizer\Anonymizer\Mapper\Node\DefaultNodeMapper;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexpParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\ArrayRuleSetParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;
use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PhpAnonymizer\Anonymizer\Processor\Factory\DefaultDataProcessorFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// 05.01.01.01 RuleSet parser (New default)

/**
 * RuleSetParser:
 * - must implement PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface
 *
 * ArrayRuleSetParser:
 * - takes optional argument $nodeParser:
 *   - must implement PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface
 *   - defaults to PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser
 *  - takes optional argument $nodeMapper:
 *    - must implement PhpAnonymizer\Anonymizer\Parser\Node\Mapper\NodeMapperInterface
 *    - defaults to PhpAnonymizer\Anonymizer\Parser\Node\Mapper\NodeMapper
 */
$ruleSetParser = new ArrayRuleSetParser(
    nodeParser: new ArrayNodeParser(),
    nodeMapper: new DefaultNodeMapper(),
);

// 05.01.01.02 RuleSet parser (Old default / deprecated)

/**
 * RuleSetParser:
 * - must implement PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface
 *
 * DefaultRuleSetParser:
 * - takes optional argument $nodeParser:
 *   - must implement PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface
 *   - defaults to PhpAnonymizer\Anonymizer\Parser\Node\SimpleRegexParser
 */
$ruleSetParser = new DefaultRuleSetParser(
    nodeParser: new ComplexRegexpParser(),
);

// 05.01.02 DependencyChecker

/**
 * DependencyChecker:
 * - must implement PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface
 *
 * DefaultDependencyChecker:
 * - takes no arguments
 * - needs composer to work
 */
$dependencyChecker = new DefaultDependencyChecker();

// 05.01.03 DataAccessProvider

/**
 * DataAccessProvider:
 * - must implement PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface
 * - can be created via factory
 *
 * DefaultDataAccessProvider:
 * - takes no arguments
 * - can have custom data access implementations registered via registerCustomDataAccess method
 */
$dataAccessProvider = new DefaultDataAccessProvider();
$dataAccessProvider->registerCustomDataAccess(
    name: 'custom',
    dataAccess: new ArrayDataAccess(),
);

/**
 * DataAccessProviderFactory:
 * - must implement PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DataAccessProviderFactoryInterface
 *
 * DefaultDataAccessProviderFactory:
 * - takes no arguments
 * - can have custom data access provider implementations registered via registerCustomDataAccessProvider method
 */
$provider = new DefaultDataAccessProviderFactory();
$provider->registerCustomDataAccessProvider(
    name: 'custom',
    definition: $dataAccessProvider,
);
$dataAccessProvider = $provider->getDataAccessProvider(
    type: 'custom',
);

// 05.01.04 DataGenerationProvider

/**
 * DataGenerationProvider:
 * - must implement PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface
 * - can be created via factory
 *
 * DefaultDataGeneratorProvider:
 * - takes required parameter $generators:
 *   - must be an array of PhpAnonymizer\Anonymizer\DataGeneration\DataGeneratorInterface
 * - takes optional argument $dependencyChecker:
 *   - must implement PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface
 *   - defaults to PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker
 * - can have custom data generator implementations registered via registerCustomDataGenerator method
 */
$dataGenerationProvider = new DefaultDataGeneratorProvider(
    generators: [
        new FakerAwareStringGenerator(
            fallbackDataGenerator: new StarMaskedStringGenerator(),
        ),
    ],
    dependencyChecker: $dependencyChecker,
);
$dataGenerationProvider->registerCustomDataGenerator(
    generator: new StarMaskedStringGenerator(),
);

/**
 * DataGenerationProviderFactory:
 * - must implement PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DataGenerationProviderFactoryInterface
 *
 * DefaultDataGenerationProviderFactory:
 * - takes no arguments
 * - can have custom data generation provider implementations registered via registerCustomDataGenerationProvider method
 */
$dataGenerationProviderFactory = new DefaultDataGenerationProviderFactory();
$dataGenerationProviderFactory->registerCustomDataGenerationProvider(
    name: 'custom',
    definition: $dataGenerationProvider,
);
$dataGenerationProvider = $dataGenerationProviderFactory->getDataGenerationProvider(
    type: 'custom',
);

// 05.01.05 DataEncodingProvider

/**
 * DataEncodingProvider:
 * - must implement PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface
 *
 * DefaultDataEncodingProvider:
 * - takes optional argument $normalizer:
 *   - must implement Symfony\Component\Serializer\Normalizer\NormalizerInterface
 *   - defaults to null
 * - takes optional argument $denormalizer:
 *   - must implement Symfony\Component\Serializer\Normalizer\DenormalizerInterface
 *   - defaults to null
 * - takes optional argument $dependencyChecker:
 *   - must implement PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface
 *   - defaults to PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker
 * - can have custom data encoder implementations registered via registerCustomDataEncoder method
 */
$dataEncodingProvider = new DefaultDataEncodingProvider();
$dataEncodingProvider->registerCustomDataEncoder(
    name: 'custom',
    encoder: new JsonEncoder(),
);

// 05.01.06 DataProcessor

/**
 * DataProcessor:
 * - must implement PhpAnonymizer\Anonymizer\DataProcessing\DataProcessorInterface
 * - can be created via factory
 *
 * DefaultDataProcessor:
 * - takes required parameter $dataAccessProvider:
 *   - must implement PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface
 * - takes required parameter $dataGenerationProvider:
 *   - must implement PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface
 * - takes required parameter $dataEncodingProvider:
 *   - must implement PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface
 */
$dataProcessor = new DefaultDataProcessor(
    dataAccessProvider: $dataAccessProvider,
    dataGenerationProvider: $dataGenerationProvider,
    dataEncodingProvider: $dataEncodingProvider,
);

/**
 * DataProcessorFactory:
 * - must implement PhpAnonymizer\Anonymizer\Processor\Factory\DataProcessorFactoryInterface
 *
 * DefaultDataProcessorFactory:
 * - takes no arguments
 * - can have custom data processor implementations registered via registerCustomDataProcessor method
 */
$processorFactory = new DefaultDataProcessorFactory();
$processorFactory->registerCustomDataProcessor(
    name: 'custom',
    definition: $dataProcessor,
);
$dataProcessor = $processorFactory->getDataProcessor(
    type: 'custom',
);

// 05.01.07 Anonymizer

/**
 * Anonymizer:
 * - must implement PhpAnonymizer\Anonymizer\AnonymizerInterface
 *
 * Anonymizer:
 * - takes required parameter $ruleSetParser:
 *   - must implement PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface
 * - takes required parameter $dataProcessor:
 *   - must implement PhpAnonymizer\Anonymizer\DataProcessing\DataProcessorInterface
 */
$anonymizer = new Anonymizer(
    ruleSetParser: $ruleSetParser,
    dataProcessor: $dataProcessor,
);
