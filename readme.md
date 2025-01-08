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
- [06 Extended Information](#06-extended-information)
    - [06.01 Manual setup of Anonymizer](#0601-manual-setup-of-anonymizer)
        - [06.01.01 RuleSet parser](#060101-ruleset-parser)
        - [06.01.02 DependencyChecker](#060102-dependencychecker)
        - [06.01.03 DataAccessProvider](#060103-dataaccessprovider)
        - [06.01.04 DataGenerationProvider](#060104-datagenerationprovider)
        - [06.01.05 DataEncodingProvider](#060105-dataencodingprovider)
        - [06.01.06 DataProcessor](#060106-dataprocessor)
        - [06.01.07 Anonymizer](#060107-anonymizer)
    - [06.02 Builder setup of Anonymizer](#0602-builder-setup-of-anonymizer)

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
    'person-data',
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

$anonymizedData = $anonymizer->run('person-data', $data);

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
    'person-data',
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
    'person-data',
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
    'person-data',
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
    'address-data',
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
    'address-data',
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
    'address-data',
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
    'address-data',
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
    'address-data',
    [
        'order.person.firstName[property#firstName]',
        'order.person.lastName[property#lastName]',
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

$anonymizedData = $anonymizer->run('person-data', $data, 'noop');
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

$anonymizedData = $anonymizer->run('person-data', $data, 'noop');
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
    'person-data',
    [
        'person.firstName',
        'person.lastName',
    ],
    DataAccess::AUTODETECT->value,
);

$anonymizedData = $anonymizer->run('person-data', $data, 'clone');
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
    'person-data',
    [],
    DataAccess::AUTODETECT->value,
);

$anonymizedData = $anonymizer->run('person-data', $data, 'clone');
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

## 06 Extended Information

### 06.01 Manual setup of Anonymizer

#### 06.01.01 RuleSet parser

```php
// examples/06_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Parser\Node\ComplexRegexParser;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;

// 06.01.01 RuleSet parser

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
    nodeParser: new ComplexRegexParser(),
);
```

#### 06.01.02 DependencyChecker

```php
// examples/06_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Dependency\DefaultDependencyChecker;

// 06.01.02 DependencyChecker

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

#### 06.01.03 DataAccessProvider

```php
// examples/06_01_manual_setup.php

use PhpAnonymizer\Anonymizer\DataAccess\ArrayDataAccess;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DefaultDataAccessProviderFactory;

// 06.01.03 DataAccessProvider

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

#### 06.01.04 DataGenerationProvider

```php
// examples/06_01_manual_setup.php

use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\FakerAwareStringGenerator;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DefaultDataGenerationProviderFactory;

// 06.01.04 DataGenerationProvider

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

#### 06.01.05 DataEncodingProvider

```php
// examples/06_01_manual_setup.php

use PhpAnonymizer\Anonymizer\DataEncoding\JsonEncoder;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;

// 06.01.05 DataEncodingProvider

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

#### 06.01.06 DataProcessor

```php
// examples/06_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PhpAnonymizer\Anonymizer\Processor\Factory\DefaultDataProcessorFactory;

// 06.01.06 DataProcessor

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

#### 06.01.07 Anonymizer

```php
// examples/06_01_manual_setup.php

use PhpAnonymizer\Anonymizer\Anonymizer;

// 06.01.07 Anonymizer

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

### 06.02 Builder setup of Anonymizer

