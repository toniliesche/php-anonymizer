# Toni's Data Anonymization Toolkit

## 00 Preliminary

### Table of Contents

- [00 Preliminary](#00-preliminary)
    - [Table of Contents](#table-of-contents)
    - [Purpose](#purpose)
    - [Getting started](#getting-started)
- [01 Basic usage](#01-basic-usage)
    - [Creating an Anonymizer instance](#creating-an-anonymizer-instance)
    - [Example Output](#example-output)
- [02 Writing definition rules](#02-writing-definition-rules)
    - [02.01 Basic rule syntax](#0201-basic-rule-syntax)
    - [02.02 Array rule syntax](#0202-array-rule-syntax)
    - [02.03 Property access syntax](#0203-property-access-syntax-complex-rule-parser-only)
    - [02.04 Fake data type annotation](#0204-fake-data-type-annotation-complex-rule-parser-only)
- [03 Using Faker as a data provider](#03-using-faker-as-a-data-provider)
    - [03.01 Use default Faker instance of builder](#0301-use-default-faker-instance-of-builder)
    - [03.02 Use custom Faker instance](#0302-use-custom-faker-instance)
    - [03.03 Set seed for Faker instance](#0303-set-seed-for-faker-instance)
- [04 Data Encoding](#04-data-encoding)
    - [04.01 NoOpEncoder](#0401-noopencoder)
    - [04.02 CloneEncoder](#0402-cloneencoder)
    - [04.03 JsonEncoder](#0403-jsonencoder)
    - [04.04 YamlEncoder](#0404-yamlencoder)
    - [04.05 SymfonyEncoder](#0405-symfonyencoder)
- [05 Extended Information](#06-extended-information)
    - [05.01 Manual setup of Anonymizer](#0501-manual-setup-of-anonymizer)
        - [05.01.01 RuleSet parser](#050101-ruleset-parser)
        - [05.01.02 DependencyChecker](#050102-dependencychecker)
        - [05.01.03 DataAccessProvider](#050103-dataaccessprovider)
        - [05.01.04 DataGenerationProvider](#050104-datagenerationprovider)
        - [05.01.05 DataEncodingProvider](#050105-dataencodingprovider)
        - [05.01.06 DataProcessor](#050106-dataprocessor)
        - [05.01.07 Anonymizer](#050107-anonymizer)
    - [05.02 Builder setup of Anonymizer](#0502-builder-setup-of-anonymizer)

### Purpose

This library is a simple data anonymization toolkit that allows to define rules for anonymizing data in a structured
way. By using this library it is possible to skip writing a lot of boilerplate code to navigate through your data
structures again and again. The library is designed to be flexible and extensible, so that it can be used in a wide
range of use cases. It also ships with support of `fakerphp/faker` as a provider for randomized fake data.

### Getting started

```bash
composer require toniliesche/anonymizer
```

## 01 Basic usage

### Creating an Anonymizer instance

If you want to start with the most basic usage of modifying data in an array structure, all you have to do is to create
an instance of the `Anonymizer` class, register a rule set by using `registerRuleSet` and call the `run` method with the
data you want to modify.

```php
// examples/01_basic_usage.php
<?php

declare(strict_types=1);

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->build();

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.first_name',
        'order.person.last_name',
    ],
);

$data = [
    'order' => [
        'person' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
    ],
];

$anonymizedData = $anonymizer->run('order', $data);

echo PHP_EOL . 'Original data:' . PHP_EOL;
print_r($data);

echo PHP_EOL . 'Anonymized data:' . PHP_EOL;
print_r($anonymizedData);
```

### Example Output

```
Original data:
Array
(
    [order] => Array
        (
            [person] => Array
                (
                    [first_name] => John
                    [last_name] => Doe
                )

        )

)

Anonymized data:
Array
(
    [order] => Array
        (
            [person] => Array
                (
                    [first_name] => ****
                    [last_name] => ***
                )

        )

)
```

## 02 Writing definition rules

### 02.01 Basic rule syntax

The default syntax when navigating the data to be anonymized is using dot notation. Every word separated by a dot
represents a level in the data structure. The following example shows how to write a rule for anonymizing the
`first_name` and `last_name` fields in the `order.person` structure.

```php
// examples/02_01_definitions_basic_rules.php

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.first_name',
        'order.person.last_name',
    ],
);
```

### 02.02 Array rule syntax

Additionally it is possible to make use of array notation to tell the anonymizer engine that there is a list of items at
a certain level. This can be realized by putting `[]` in front of a keyword.

```php
// examples/02_02_definitions_array_rules.php

$anonymizer->registerRuleSet(
    'order',
    [
        '[]orders.person.first_name',
        '[]orders.person.last_name',
    ],
);
```

### 02.03 Property access syntax *[complex rule parser only]*

The previous examples all assumed that the data structure to be passed is an array. Apart from that this library also
supports different ways of accessing object properties. This can be passed to any layer directly *after* the name of the
property.

Note: The definition of the access method is optional. If omitted, the anonymizer will fall back to the configured
default access method.

Example with direct property access on object. This methods requires the properties to be *public* and *not readonly*.

```php
// examples/02_03_01_definitions_property_access_by_property.php

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.firstName[property]',
        'order.person.lastName[property]',
    ],
);
```

Example with property access via getter and setter method. This method requires the properties to have a matching
*getPropertyName* and *setPropertyName* method.

```php
// examples/02_03_02_definitions_property_access_by_setter.php

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.firstName[setter]',
        'order.person.lastName[setter]',
    ],
);
```

The safest way to access properties on objects, is to use reflection.

```php
// examples/02_03_03_definitions_property_access_via_reflection.php

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.firstName[reflection]',
        'order.person.lastName[reflection]',
    ],
);
```

Of course it is also possible to mix these access methods in one rule set and make the array property access more
verbose.

```php
// 02_03_04_definitions_property_access_mixed.php

$anonymizer->registerRuleSet(
    'order',
    [
        '[]orders[array].person[setter].firstName[property]',
        '[]orders[array].person[setter].lastName[property]',
    ],
);
```

In case there are any more specific requirements for accessing object properties, it is possible to implement a custom
data accessor by implementing the `PhpAnonymizer\Anonymizer\DataAccess\DataAccessorInterface`.

Supported access methods as of now are:

| Access Method | Description                                           |
|---------------|-------------------------------------------------------|
| `array`       | Access array elements by key name                     |
| `property`    | Access object properties by name                      |
| `reflection`  | Access object properties via reflection classes       |
| `setter`      | Access object properties by getter and setter methods |

### 02.04 Fake data type annotation *[complex rule parser only]*

! Notice: This feature CANNOT be combined with the nested data type annotation.

Until now all that we have achieved, is to replace the data with starred out place holders that have the same length as
the original data. As sometimes it is more desirable to replace the data with more real world-like fake data, it is
possible to tell the Anonymizer which kind of data we want to set as a field's replacement.

Note: to get this feature working, the `fakerphp/faker` library must be installed. See section
`03 Using Faker as a data provider` for more information.

To introduce the use of fake data, you can add a type annotation to the property field with a precending `#` symbol
within the square brackets, e.g. `order.person.firstName[#firstName]`.

```php
// examples/02_04_01_definitions_fake_data.php

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.firstName[#firstName]',
        'order.person.lastName[#lastName]',
    ],
);
```

Of course, also in this case it is possible to mix the fake data type annotations with the other access methods. In this
case, the fake data type must be preceded by the access method, e.g. `order.person.firstName[property#firstName]`.

```php
// examples/02_04_02_definitions_fake_data_with_property_access.php

$anonymizer->registerRuleSet(
    'order',
    [
        'order.person.firstName[property#firstName]',
        'order.person.lastName[property#lastName]',
    ],
);
```

### 02.05 Nested data type annotation *[complex rule parser only]*

! Notice: This feature CANNOT be combined with the fake data type annotation.

In some cases it can be necessary to define a way of anonymizing data that have been stored in a nested data type. This can happen when a string formatted field contains a complete json document for example. In this case we need two information to be defined: The type of the nested data (e.g. json) and the rule set that should handle the nested data (as this data is handled as a separate object).

The data type will be annotated after a leading `?` symbol, followed by a `/` separated rule name, e.g. `order.address[?json/address]`.

```php
// examples/02_05_01_definitions_nested_data.php

$anonymizer->registerRuleSet(
    'order',
    [
        'order.address[?json/address]',
    ],
);
```

And again, also in this case it is possible to mix the nested data type annotations with the other access methods. In this case, the fake data type must be preceded by the access method, e.g. `order.address[property?json/address]`.

```php
// examples/02_05_02_definitions_nested_data_with_property_access.php

$anonymizer->registerRuleSet(
    'order',
    [
        'order.address[property?json/address]',
    ],
);
```

## 03 Using Faker as a data provider

Before using Faker as a data provider, you need to install the `fakerphp/faker` library. This can be realized by issuing
a simple composer command.

```bash
composer require fakerphp/faker
```

Afterwards, you need to register an instance of Faker to the Anonymizer instance. There are different ways of achieving
this.

### 03.01 Use default Faker instance of builder

By default it is possible to use the generic Faker instance that is shipped with the AnonymizerBuilder. This instance is
created with the default locale `en_US` and all default providers.

```php
// examples/03_01_default_faker_instance.php

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;

$builder = (new AnonymizerBuilder())
    ->withDefaults()
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->withFaker(true)
    ->build();
```

### 03.02 Use custom Faker instance

If you want to have more control over how the Faker instance is created, you can pass an instance of `Faker\Generator`
directly to the AnonymizerBuilder.

```php
// examples/03_02_custom_faker_instance.php

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use Faker\Factory;

$faker = Factory::create('de_DE');

$builder = (new AnonymizerBuilder())
    ->withDefaults()
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->withCustomFaker($faker)
    ->build();
```

### 03.03 Set seed for Faker instance

It is also possible to set a seed for the Faker instance. This can be useful if you want to have reproducible results.
In our case we use a string as a keyword that will be hashed to an integer value (md5).

```php
// examples/03_03_seeded_faker_instance.php

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;

$builder = (new AnonymizerBuilder())
    ->withDefaults()
    ->withNodeParserType(NodeParser::COMPLEX->value)
    ->withFaker(true)
    ->withFakerSeed('my_seed')
    ->build();
```

## 04 Data Encoding

The `Analyzer` main service class supports the use of a data encoding class. The sole purpose of this data encoding
class is to make the input data accessible for transformation:

| Encoder               | Description                    | Input Encode | Output Encode | Input Decode | Output Decode |
|-----------------------|--------------------------------|--------------|---------------|--------------|---------------|
| NoOpEncoder           | does not change the input data | `mixed`      | `mixed`       | `mixed`      | `mixed`       |
| CloneEncoder          | clones objects on decode       | `mixed`      | `mixed`       | `mixed`      | `mixed`       |
| JsonEncoder           | encodes data as JSON           | `array`      | `string`      | `string`     | `array`       |
| YamlEncoder           | encodes data as YAML           | `array`      | `string`      | `string`     | `array`       |
| SymfonyEncoder        | transforms objects to array    | `object`     | `array`       | `array`      | `object`      |
| SymfonyToJsonEncoder  | transforms object to json      | `object`     | `array`       | `array`      | `string`      |
| SymfonyToArrayEncoder | transforms objects to array    | `object`     | `array`       | `array`      | `array`       |
| ArrayToJsonEncoder    | transforms array to json       | `array`      | `array`       | `array`      | `string`      |

### 04.01 NoOpEncoder

The `NoOpEncoder` simply does nothing. It takes an argument and passes the same object back to the consumer on both
methods (`encode`and `decode`).

Notice: It will pass arguments by value, not by reference, so if you pass a non-object value, it will NOT update the
input variable as long as you don't override it manually.

```php
// examples/04_01_01_noop_encoder_array.php

$data = [
    'order' => [
        'person' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
    ],
];

$anonymizedData = $anonymizer->run('order', $data, 'noop');
```

Example output for the noop encoder on an array.

```
Check $data == $anonymizedData
bool(false)

Check $data === $anonymizedData
bool(false)
```

```php
// examples/04_01_02_noop_encoder_object.php

$person = new Person(
    'John',
    'Doe'
);

$data = [
    'order' => [
        'person' => $person,
    ],
];

$anonymizedData = $anonymizer->run('order', $data, 'noop');
```

Example output for the noop encoder on an object.

```
Check $data == $anonymizedData
bool(true)

Check $data === $anonymizedData
bool(true)
```

### 04.02 CloneEncoder

When calling the `decode` method of the `CloneEncoder`, this encoder creates a COPY of the input values.

By this mean, the Anonymizer will update the objects within the data to be anonymized on a new copy of the object,
rather than manipulating the initial object.

As the `clone` keyword only creates a shallow copy of the top level object, we use the very popular library
`myclabs/deep-copy` to achieve a recursive cloning of all nested objects.

```php
// examples/04_02_01_clone_encoder_change.php

$anonymizer->registerRuleSet(
    'order',
    [
        'person.firstName',
        'person.lastName',
    ],
    DataAccess::AUTODETECT->value,
);

$anonymizedData = $anonymizer->run('order', $data, 'clone');
```

Example output for the clone encoder after data has been changed.

```
Check $data == $anonymizedData
bool(false)

Check $data === $anonymizedData
bool(false)
```

In the next step, we change our rule set to not modify any data within the cloned object.

```php
// examples/04_02_02_clone_encoder_no_change.php

$anonymizer->registerRuleSet(
    'order',
    [],
    DataAccess::AUTODETECT->value,
);

$anonymizedData = $anonymizer->run('order', $data, 'clone');
```

Example output for the clone encoder after data hasn't been changed.

```
Check $data == $anonymizedData
bool(true)

Check $data === $anonymizedData
bool(false)
```

### 04.03 JsonEncoder

The `JsonEncoder` is an encoder that can help you handle json data. With this encoder it is possible to modify sensitive
data directly within the json document's string representation.

For the encoder to work, the `json` php extension is required (which is part of the php core since 8.0 anyway).

The `decode` method will transform a json `string` into an `array`, the `encode` method will transform an `array` back
into json `string` notation (single line without PRETTY_PRINT).

### 04.04 YamlEncoder

The `YamlEncoder` is an encoder that can help you handle yaml data. With this encoder it is possible to modify sensitive
data directly within the yaml document's string representation.

For the encoder to work, the `yaml` php extension is required. This can be installed via pecl, for instance. Some Linux
distributions also offer pre-compiled packages as an alternative to manual building already.

The `decode` method will transform a yaml `string` into an `array`, the `encode` method will transform an `array` back
into yaml `string` notation.

### 04.05 SymfonyEncoder

The last encoder is the `SymfonyEncoder`. This encoder is a bit more complex than the others, as it is able to transform
objects into arrays and vice versa.

For this encoder to work, you will need to have the `symfony/serializer` package installed and have to setup a
Normalizer and a Denormalizer that follow Symfony's NormalizerInterface and DenormalizerInterface (e.g.
ObjectNormalizer).

To make the `SymfonyEncoder` work, it is essential that your object can be normalized and denormalized properly by using
these Normalizer and Denormalizer objects.

You can install the `symfony/serializer` package via composer:

```bash
composer require symfony/serializer
```

## 05 Extended Information

In this section you will learn about possibilities on how to setup the anonymizing toolkit for your own application.
Currently there are two main options:

- manual setup by wiring everything together by hand
- using the builder toolkit for assisted setup

### 05.01 Manual setup of Anonymizer

#### 05.01.01 RuleSet parser

The ruleset parser is the central part of the rule setup engine. It defines in which way the rules are parsed from the
input array. In general, it is possible to define the rules in multiple ways. Currently there are two default supported
syntaxes:

- array based rules
- string based rules (using the old, deprecated regular expression parser)

```php
// examples/05_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Mapper\Node\DefaultNodeMapper;
use PhpAnonymizer\Anonymizer\Parser\Node\ArrayNodeParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\ArrayRuleSetParser;

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
```

```php
// examples/05_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexpParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;

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
```

#### 05.01.02 DependencyChecker

The DependencyChecker is a small utility that helps you identifying the support of optional parts of the software. It
comes with support for installed php extensions as well as loaded composer dependencies.

```php
// examples/05_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;

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
```

#### 05.01.03 DataAccessProvider

DataAccessProviders provide access classes to iterate through the data tree of to be anonymized data. It contains
different DataAccess implementations to access array keys, getters, object variables etc. depending on your ruleset.

```php
// examples/05_01_manual_setup.php

use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DefaultDataAccessProviderFactory;

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
$dataAccessProvider->registerCustomDataAccess('custom', new ArrayDataAccess());

/**
 * DataAccessProviderFactory:
 * - must implement PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DataAccessProviderFactoryInterface
 *
 * DefaultDataAccessProviderFactory:
 * - takes no arguments
 * - can have custom data access provider implementations registered via registerCustomDataAccessProvider method
 */
$provider = new DefaultDataAccessProviderFactory();
$provider->registerCustomDataAccessProvider('custom', $dataAccessProvider);
$dataAccessProvider = $provider->getDataAccessProvider('custom');
```

#### 05.01.04 DataGenerationProvider

Setting up a DataGenerationProvider is necessary to determine, how you want to replace the anonymized data. It offers
different strategies based on your setup, such as * replacements or Faker-based fake data.

```php
// examples/05_01_manual_setup.php

use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\FakerAwareStringGenerator;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DefaultDataGenerationProviderFactory;

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
        new FakerAwareStringGenerator(new StarMaskedStringGenerator()),
    ],
    dependencyChecker: $dependencyChecker,
);
$dataGenerationProvider->registerCustomDataGenerator(new StarMaskedStringGenerator());

/**
 * DataGenerationProviderFactory:
 * - must implement PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DataGenerationProviderFactoryInterface
 *
 * DefaultDataGenerationProviderFactory:
 * - takes no arguments
 * - can have custom data generation provider implementations registered via registerCustomDataGenerationProvider method
 */
$dataGenerationProviderFactory = new DefaultDataGenerationProviderFactory();
$dataGenerationProviderFactory->registerCustomDataGenerationProvider('custom', $dataGenerationProvider);
$dataGenerationProvider = $dataGenerationProviderFactory->getDataGenerationProvider('custom');
```

#### 05.01.05 DataEncodingProvider

A DataEncodingProvider is needed to transform the input and output of the anonymization by using the desired data
formats. This way it is possible to change the output into of your desired data format.

```php
// examples/05_01_manual_setup.php

use PhpAnonymizer\Anonymizer\DataEncoding\JsonEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;

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
$dataEncodingProvider->registerCustomDataEncoder('custom', new JsonEncoder());
```

#### 05.01.06 DataProcessor

The DataProcessor is the actual processing part that glues together all parts of the anonymization engine. It's the core
interface between data access, data encoding and data generation.

```php
// examples/05_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PhpAnonymizer\Anonymizer\Processor\Factory\DefaultDataProcessorFactory;

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
$processorFactory->registerCustomDataProcessor('custom', $dataProcessor);
$dataProcessor = $processorFactory->getDataProcessor('custom');
```

#### 05.01.07 Anonymizer

Once, all dependencies have been set up, we can wire together the Anonymizer and pass ruleset parser and data processor
to make it a working service.

```php
// examples/05_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Anonymizer;

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
```

### 05.02 Builder setup of Anonymizer

