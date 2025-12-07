# Toni's Data Anonymization Toolkit

## 00 Preliminary

### Project Status

[![Tests](https://github.com/toniliesche/php-anonymizer/actions/workflows/ci.yml/badge.svg)](https://github.com/toniliesche/php-anonymizer/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/toniliesche/php-anonymizer/branch/develop/graph/badge.svg)](https://codecov.io/gh/toniliesche/php-anonymizer)

### Project Info

![PHP Version](https://img.shields.io/packagist/php-v/php-anonymizer/anonymizer) [![Latest Version](https://img.shields.io/packagist/v/php-anonymizer/anonymizer.svg)](https://packagist.org/packages/php-anonymizer/anonymizer) ![License](https://img.shields.io/github/license/toniliesche/php-anonymizer)

### License

This project is released under MIT license.

See the [LICENSE](LICENSE.md) for more information.

### Table of Contents

- [00 Preliminary](#00-preliminary)
    - [Project Status](#project-status)
    - [Project Info](#project-info)
    - [License](#license)
    - [Table of Contents](#table-of-contents)
    - [Purpose](#purpose)
    - [Getting started](#getting-started)
- [01 Basic usage](#01-basic-usage)
    - [Creating an Anonymizer instance](#creating-an-anonymizer-instance)
    - [Example Output](#example-output)
- [02 Writing definition rules](#02-writing-definition-rules)
    - [02.01 Array based syntax](#0201-array-based-syntax)
    - [02.02 Regular Expression Based Syntax (deprecated)](#0202-regular-expression-based-syntax-deprecated)
        - [02.02.01 Basic rule syntax](#020201-basic-rule-syntax)
        - [02.02.02 Array rule syntax](#020202-array-rule-syntax)
        - [02.02.03 Property access syntax](#020203-property-access-syntax-complex-rule-parser-only)
        - [02.02.04 Fake data type annotation](#020204-fake-data-type-annotation-complex-rule-parser-only)
        - [02.02.05 Nested data type annotation](#020205-nested-data-type-annotation-complex-rule-parser-only)
- [03 Using Faker as a data provider](#03-using-faker-as-a-data-provider)
    - [03.01 Use default Faker instance of builder](#0301-use-default-faker-instance-of-builder)
    - [03.02 Use custom Faker instance](#0302-use-custom-faker-instance)
    - [03.03 Set seed for Faker instance](#0303-set-seed-for-faker-instance)
- [04 Data Encoding](#04-data-encoding)
    - [04.01 NoOpEncoder](#0401-noopencoder)
    - [04.02 CloneEncoder](#0402-cloneencoder)
    - [04.03 JsonEncoder](#0403-jsonencoder)
    - [04.04 YamlEncoder](#0404-yamlencoder)
    - [04.05 Array2JsonEncoder](#0405-array2jsonencoder)
    - [04.06 SymfonyEncoder](#0406-symfonyencoder)
    - [04.07 Symfony2JsonEncoder](#0407-symfony2jsonencoder)
    - [04.08 Symfony2ArrayEncoder](#0408-symfony2arrayencoder)
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
      - [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder)
      - [05.02.02 Setting Defaults](#050202-setting-defaults)
      - [05.02.03 Setting NodeParser](#050203-setting-nodeparser)
      - [05.02.04 Setting NodeMapper](#050204-setting-nodemapper)
      - [05.02.05 Setting DataAccessProvider](#050205-setting-dataccessprovider)
      - [05.02.06 Setting DataGenerationProvider](#050206-setting-datagenerationprovider)
      - [05.02.07 Enabling Faker](#050207-enabling-faker)
      - [05.02.08 Setting Custom Faker](#050208-setting-custom-faker)
      - [05.02.09 Setting Faker Seed](#050209-setting-faker-seed)
      - [05.02.10 Setting Rule Loader](#050210-setting-rule-loader)
      - [05.02.11 Setting RuleSetParser](#050211-setting-rulesetparser)
      - [05.02.12 Setting DataProcessor](#050212-setting-dataprocessor)

### Purpose

This library is a simple data anonymization toolkit that allows to define rules for anonymizing data in a structured
way. By using this library it is possible to skip writing a lot of boilerplate code to navigate through your data
structures again and again. The library is designed to be flexible and extensible, so that it can be used in a wide
range of use cases. It also ships with support of `fakerphp/faker` as a provider for randomized fake data.

### Getting started

```bash
composer require php-anonymizer/anonymizer
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
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'first_name',
                        ],
                        [
                            'name' => 'last_name',
                        ],
                    ],
                ],
            ],
        ],
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

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
);


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

### 02.01 Array based syntax

This syntax is defining the anonymization data structure in an array tree. This makes it easy to extend and also allows
the use of json / yaml inputs without much overhead.

*This is the new default syntax. If you are still using the old regular expression based syntax, it is highly advisable
to start migrating to this new syntax as the old version will not receive any updates and may lack the latest features.*

#### 02.01.01 Basic rule syntax

A node only needs one mandatory field which is `name` to define the property name of our to be anonymized data. On top
it is possible to define `children` within this tree. Only the leafs will be anonymized as these are the only scalar
values. The following example shows how to write a rule for anonymizing the `first_name` and `last_name` fields in the
`order.person` structure.

```php
// examples/02_01_01_definitions_basic_rules.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'first_name',
                        ],
                        [
                            'name' => 'last_name',
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

#### 02.01.02 Array rule syntax

Additionally it is possible to make use of array notation to tell the anonymizer engine that there is a list of items at
a certain level. This can be realized by setting the option `is_array` to true.

```php
// examples/02_01_02_definitions_array_rules.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'orders',
            'is_array' => true, // mark this layer to be a list of person objects
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'first_name',
                        ],
                        [
                            'name' => 'last_name',
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

#### 02.01.03 Property access syntax

The previous examples all assumed that the data structure to be passed is an array. Apart from that this library also
supports different ways of accessing object properties. This can be passed to any layer directly by setting the option
`data_access`.

Note: The definition of the access method is optional. If omitted, the anonymizer will fall back to the configured
default access method.

Example with direct property access on object. This methods requires the properties to be *public* and *not readonly*.

```php
// examples/02_01_03_01_definitions_property_access_by_property.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'firstName',
                            'data_access' => 'property', // add data_access setting here to define access method
                        ],
                        [
                            'name' => 'lastName',
                            'data_access' => 'property', // add data_access setting here to define access method
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

Example with property access via getter and setter method. This method requires the properties to have a matching
*getPropertyName* and *setPropertyName* method.

```php
// examples/02_02_03_02_definitions_property_access_by_setter.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'firstName',
                            'data_access' => 'setter', // add data_access setting here to define access method
                        ],
                        [
                            'name' => 'lastName',
                            'data_access' => 'setter', // add data_access setting here to define access method
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

The safest way to access properties on objects, is to use reflection.

```php
// examples/02_02_03_03_definitions_property_access_via_reflection.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'firstName',
                            'data_access' => 'reflection', // add data_access setting here to define access method
                        ],
                        [
                            'name' => 'lastName',
                            'data_access' => 'reflection', // add data_access setting here to define access method
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

Of course it is also possible to mix these access methods in one rule set and make the array property access more
verbose.

```php
// examples/02_02_03_04_definitions_property_access_mixed.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'orders',
            'is_array' => true,
            'children' => [
                [
                    'name' => 'person',
                    'data_access' => 'setter', // add data_access setting here to define access method
                    'children' => [
                        [
                            'name' => 'firstName',
                            'data_access' => 'property', // add data_access setting here to define access method
                        ],
                        [
                            'name' => 'lastName',
                            'data_access' => 'property', // add data_access setting here to define access method
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

In case there are any more specific requirements for accessing object properties, it is possible to implement a custom
data accessor by implementing the `PhpAnonymizer\Anonymizer\DataAccess\DataAccessorInterface`.

Supported access methods as of now are:

| Access Method | Description                                           |
|---------------|-------------------------------------------------------|
| `autodetect`  | Access object properties via autodetection method     |
| `array`       | Access array elements by key name                     |
| `property`    | Access object properties by name                      |
| `reflection`  | Access object properties via reflection classes       |
| `setter`      | Access object properties by getter and setter methods |

### 02.01.04 Fake data type annotation *[complex rule parser only]*

! Notice: This feature CANNOT be combined with the nested data type annotation.

Until now all that we have achieved, is to replace the data with starred out place holders that have the same length as
the original data. As sometimes it is more desirable to replace the data with more real world-like fake data, it is
possible to tell the Anonymizer which kind of data we want to set as a field's replacement.

Note: to get this feature working, the `fakerphp/faker` library must be installed. See section
`03 Using Faker as a data provider` for more information.

To introduce the use of fake data, you can add an `value_type` option to any leaf node within the tree.

```php
// examples/02_01_04_01_definitions_fake_data.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'firstName',
                            'value_type' => 'firstName', // add value_type option to define faker value to be used
                        ],
                        [
                            'name' => 'lastName',
                            'value_type' => 'lastName', // add value_type option to define faker value to be used
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

Of course, also in this case it is possible to mix the fake data type annotations with the other access methods.

```php
// examples/02_01_04_02_definitions_fake_data_with_property_access.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'firstName',
                            'data_access' => 'property',
                            'value_type' => 'firstName',
                        ],
                        [
                            'name' => 'lastName',
                            'data_access' => 'property',
                            'value_type' => 'lastName',
                        ],
                    ],
                ],
            ],
        ],
    ],
);
```

### 02.01.05 Nested data type annotation

! Notice: This feature CANNOT be combined with the fake data type annotation.

In some cases it can be necessary to define a way of anonymizing data that have been stored in a nested data type. This can happen when a string formatted field contains a complete json document for example. In this case we need two information to be defined: The type of the nested data (e.g. json) and the rule set that should handle the nested data (as this data is handled as a separate object).

To use this function, you need to add both a `nested_type` and a `nested_rule` option to a leaf node.

```php
// examples/02_01_05_01_definitions_nested_data.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'address',
                    'nested_rule' => 'address', // add rule name here
                    'nested_type' => 'json',    // add info on how to resolve child data
                ],
            ],
        ],
    ],
);
```

And again, also in this case it is possible to mix the nested data type annotations with the other access methods.

```php
// examples/02_01_05_02_definitions_nested_data_with_property_access.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'address',
                    'data_access' => 'property',
                    'nested_rule' => 'address',
                    'nested_type' => 'json',
                ],
            ],
        ],
    ],
);
```


### 02.02 Regular expression based syntax (deprecated)

The regular expression based syntax is the old default. In this case every line contains a complete path to a node that
is defined to be anonymized. It is important that multiple definitions of a node may not conflict.

*This syntax is deprecated and will not receive any new features. It is suggested to migrate to the new default
array-syntax instead as this version will be removed in an upcoming stable release.*

#### 02.02.01 Basic rule syntax

The default syntax when navigating the data to be anonymized is using dot notation. Every word separated by a dot
represents a level in the data structure. The following example shows how to write a rule for anonymizing the
`first_name` and `last_name` fields in the `order.person` structure.

```php
// examples/02_02_01_definitions_basic_rules.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.person.first_name',
        'order.person.last_name',
    ],
);
```

#### 02.02.02 Array rule syntax

Additionally it is possible to make use of array notation to tell the anonymizer engine that there is a list of items at
a certain level. This can be realized by putting `[]` in front of a keyword.

```php
// examples/02_02_02_definitions_array_rules.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        // the [] in front of orders mark this layer to be a list of person objects
        '[]orders.person.first_name',
        '[]orders.person.last_name',
    ],
);
```

#### 02.02.03 Property access syntax *[complex rule parser only]*

The previous examples all assumed that the data structure to be passed is an array. Apart from that this library also
supports different ways of accessing object properties. This can be passed to any layer directly *after* the name of the
property.

Note: The definition of the access method is optional. If omitted, the anonymizer will fall back to the configured
default access method.

Example with direct property access on object. This methods requires the properties to be *public* and *not readonly*.

```php
// examples/02_02_03_01_definitions_property_access_by_property.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.person.firstName[property]',
        'order.person.lastName[property]',
    ],
);
```

Example with property access via getter and setter method. This method requires the properties to have a matching
*getPropertyName* and *setPropertyName* method.

```php
// examples/02_02_03_02_definitions_property_access_by_setter.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.person.firstName[setter]',
        'order.person.lastName[setter]',
    ],
);
```

The safest way to access properties on objects, is to use reflection.

```php
// examples/02_02_03_03_definitions_property_access_via_reflection.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.person.firstName[reflection]',
        'order.person.lastName[reflection]',
    ],
);
```

Of course it is also possible to mix these access methods in one rule set and make the array property access more
verbose.

```php
// examples/02_02_03_04_definitions_property_access_mixed.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
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

### 02.02.04 Fake data type annotation *[complex rule parser only]*

! Notice: This feature CANNOT be combined with the nested data type annotation.

Until now all that we have achieved, is to replace the data with starred out place holders that have the same length as
the original data. As sometimes it is more desirable to replace the data with more real world-like fake data, it is
possible to tell the Anonymizer which kind of data we want to set as a field's replacement.

Note: to get this feature working, the `fakerphp/faker` library must be installed. See section
`03 Using Faker as a data provider` for more information.

To introduce the use of fake data, you can add a type annotation to the property field with a precending `#` symbol
within the square brackets, e.g. `order.person.firstName[#firstName]`.

```php
// examples/02_02_04_01_definitions_fake_data.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.person.firstName[#firstName]',
        'order.person.lastName[#lastName]',
    ],
);
```

Of course, also in this case it is possible to mix the fake data type annotations with the other access methods. In this
case, the fake data type must be preceded by the access method, e.g. `order.person.firstName[property#firstName]`.

```php
// examples/02_02_04_02_definitions_fake_data_with_property_access.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.person.firstName[property#firstName]',
        'order.person.lastName[property#lastName]',
    ],
);
```

### 02.02.05 Nested data type annotation *[complex rule parser only]*

! Notice: This feature CANNOT be combined with the fake data type annotation.

In some cases it can be necessary to define a way of anonymizing data that have been stored in a nested data type. This can happen when a string formatted field contains a complete json document for example. In this case we need two information to be defined: The type of the nested data (e.g. json) and the rule set that should handle the nested data (as this data is handled as a separate object).

The data type will be annotated after a leading `?` symbol, followed by a `/` separated rule name, e.g. `order.address[?json/address]`.

```php
// examples/02_02_05_01_definitions_nested_data.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        'order.address[?json/address]',
    ],
);
```

And again, also in this case it is possible to mix the nested data type annotations with the other access methods. In this case, the fake data type must be preceded by the access method, e.g. `order.address[property?json/address]`.

```php
// examples/02_02_05_02_definitions_nested_data_with_property_access.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
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
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;

$builder = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    // set faker to true here to use the default faker instance
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
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;
use Faker\Factory;

$faker = Factory::create('de_DE');

$builder = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    // pass custom Faker instance here
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
use PhpAnonymizer\Anonymizer\Enum\RuleSetParser;

$builder = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->withFaker(true)
    // pass custom faker random seed here
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

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'noop',
);
```

Example output for the noop encoder on an array.

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

Check $data == $anonymizedData
bool(false)

Check $data === $anonymizedData
bool(false)
```

```php
// examples/04_01_02_noop_encoder_object.php

$person = new Person(
    firstName: 'John',
    lastName: 'Doe',
);

$data = [
    'order' => [
        'person' => $person,
    ],
];

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'noop',
);
```

Example output for the noop encoder on an object.

```
Original data:
Array
(
    [order] => Array
        (
            [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
                (
                    [firstName] => ****
                    [lastName] => ***
                )

        )

)

Anonymized data:
Array
(
    [order] => Array
        (
            [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
                (
                    [firstName] => ****
                    [lastName] => ***
                )

        )

)

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
    name: 'order',
    definitions: [
        [
            'name' => 'person',
            'children' => [
                [
                    'name' => 'firstName',
                ],
                [
                    'name' => 'lastName',
                ],
            ],
        ],
    ],
    defaultDataAccess: DataAccess::AUTODETECT->value,
);

$person = new Person(
    firstName: 'John',
    lastName: 'Doe',
);

$data = new Order(
    person: $person,
);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'clone',
);
```

Example output for the clone encoder after data has been changed.

```
Original data:
PhpAnonymizer\Anonymizer\Examples\Order Object
(
    [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
        (
            [firstName] => John
            [lastName] => Doe
        )

)

Anonymized data:
PhpAnonymizer\Anonymizer\Examples\Order Object
(
    [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
        (
            [firstName] => ****
            [lastName] => ***
        )

)

Check $data == $anonymizedData
bool(false)

Check $data === $anonymizedData
bool(false)
```

In the next step, we change our rule set to not modify any data within the cloned object.

```php
// examples/04_02_02_clone_encoder_no_change.php

$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
    ],
    defaultDataAccess: DataAccess::AUTODETECT->value,
);

$person = new Person(
    firstName: 'John',
    lastName: 'Doe',
);

$data = new Order(
    $person,
);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'clone',
);
```

Example output for the clone encoder after data hasn't been changed.

```
Original data:
PhpAnonymizer\Anonymizer\Examples\Order Object
(
    [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
        (
            [firstName] => John
            [lastName] => Doe
        )

)

Anonymized data:
PhpAnonymizer\Anonymizer\Examples\Order Object
(
    [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
        (
            [firstName] => John
            [lastName] => Doe
        )

)

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

```php
// examples/04_03_json_encoder.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'first_name',
                        ],
                        [
                            'name' => 'last_name',
                        ],
                    ],
                ],
            ],
        ],
    ],
);

$data = json_encode([
    'order' => [
        'person' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
    ],
], JSON_THROW_ON_ERROR);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'json',
);
```

Example output for the json encoder after data has been changed.

```
Original data:
{"order":{"person":{"first_name":"John","last_name":"Doe"}}}

Anonymized data:
{"order":{"person":{"first_name":"****","last_name":"***"}}}
```

### 04.04 YamlEncoder

The `YamlEncoder` is an encoder that can help you handle yaml data. With this encoder it is possible to modify sensitive
data directly within the yaml document's string representation.

For the encoder to work, the `yaml` php extension is required. This can be installed via pecl, for instance. Some Linux
distributions also offer pre-compiled packages as an alternative to manual building already.

The `decode` method will transform a yaml `string` into an `array`, the `encode` method will transform an `array` back
into yaml `string` notation.

```php
// examples/04_04_yaml_encoder.php

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'order',
            'children' => [
                [
                    'name' => 'person',
                    'children' => [
                        [
                            'name' => 'first_name',
                        ],
                        [
                            'name' => 'last_name',
                        ],
                    ],
                ],
            ],
        ],
    ],
);

$data = yaml_emit([
    'order' => [
        'person' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
    ],
]);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'yaml',
);
```

Example output for the yaml encoder after data has been changed.

```
Original data:
---
order:
  person:
    first_name: John
    last_name: Doe
...


Anonymized data:
---
order:
  person:
    first_name: '****'
    last_name: '***'
...

```

### 04.05 Array2JsonEncoder

```php
// examples/04_05_array2json_encoder.php

$data = [
    'order' => [
        'person' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
    ],
];

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'array2json',
);
```

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
{"order":{"person":{"first_name":"****","last_name":"***"}}}
```

### 04.06 SymfonyEncoder

The last encoder is the `SymfonyEncoder`. This encoder is a bit more complex than the others, as it is able to transform
objects into arrays and vice versa.

For this encoder to work, you will need to have the `symfony/serializer` package installed and have to setup a
Normalizer and a Denormalizer that follow Symfony's NormalizerInterface and DenormalizerInterface (e.g.
ObjectNormalizer).

To make the `SymfonyEncoder` work, it is essential that your object can be normalized and denormalized properly by using
these Normalizer and Denormalizer objects.

You can install the `symfony/serializer` package via composer:

```bash
composer require symfony/serializer-pack
```

```php
// examples/04_06_symfony_encoder.php

$serializer = (new SerializerBuilder())->withDefaults()->build();
$anonymizer = (new AnonymizerBuilder())
    ->withDefaults()
    ->withRuleSetParserType(RuleSetParser::ARRAY->value)
    ->withNodeParserType(NodeParser::ARRAY->value)
    ->withNormalizer($serializer)
    ->withDenormalizer($serializer)
    ->build();

$anonymizer->registerRuleSet(
    name: 'order',
    definitions: [
        [
            'name' => 'person',
            'children' => [
                [
                    'name' => 'firstName',
                ],
                [
                    'name' => 'lastName',
                ],
            ],
        ],
    ],
    defaultDataAccess: DataAccess::AUTODETECT->value,
);

$person = new Person(
    firstName: 'John',
    lastName: 'Doe',
);

$data = new Order(
    person: $person,
);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'symfony',
);
```

```
Original data:
PhpAnonymizer\Anonymizer\Examples\Order Object
(
    [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
        (
            [firstName] => John
            [lastName] => Doe
        )

)


Anonymized data:
PhpAnonymizer\Anonymizer\Examples\Order Object
(
    [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
        (
            [firstName] => ****
            [lastName] => ***
        )

)


Check $data == $anonymizedData
bool(false)


Check $data === $anonymizedData
bool(false)
```

### 04.07 Symfony2JsonEncoder

```php
// examples/04_07_symfony2json_encoder.php

$person = new Person(
    firstName: 'John',
    lastName: 'Doe',
);

$data = new Order(
    person: $person,
);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'symfony2json',
);
```

```
Original data:
PhpAnonymizer\Anonymizer\Examples\Order Object
(
    [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
        (
            [firstName] => John
            [lastName] => Doe
        )

)


Anonymized data:
{"person":{"firstName":"****","lastName":"***"}}

Check $data == $anonymizedData
bool(false)


Check $data === $anonymizedData
bool(false)
```

### 04.08 Symfony2ArrayEncoder

```php
// examples/04_08_symfony2array_encoder.php

$person = new Person(
    firstName: 'John',
    lastName: 'Doe',
);

$data = new Order(
    person: $person,
);

$anonymizedData = $anonymizer->run(
    ruleSetName: 'order',
    data: $data,
    // pass encoder to use here
    encoding: 'symfony2array',
);
```

```
Original data:
PhpAnonymizer\Anonymizer\Examples\Order Object
(
    [person] => PhpAnonymizer\Anonymizer\Examples\Person Object
        (
            [firstName] => John
            [lastName] => Doe
        )

)


Anonymized data:
Array
(
    [person] => Array
        (
            [firstName] => ****
            [lastName] => ***
        )

)


Check $data == $anonymizedData
bool(false)


Check $data === $anonymizedData
bool(false)
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

### 05.02 Builder-based Setup of Anonymizer

#### 05.02.01 Creating AnonymizerBuilder

AnonymizerBuilder:
- takes optional argument `$nodeParserFactory`:
  - must implement `PhpAnonymizer\Anonymizer\Parser\Node\Factory\NodeParserFactoryInterface`
  - defaults to `DefaultNodeParserFactory`
- takes optional argument `$ruleSetParserFactory`:
  - must implement `PhpAnonymizer\Anonymizer\Parser\RuleSet\Factory\RuleSetParserFactoryInterface`
  - defaults to `DefaultRuleSetParserFactory`
- takes optional argument `$dataProcessorFactory`:
  - must implement `PhpAnonymizer\Anonymizer\Processor\Factory\DataProcessorFactoryInterface`
  - defaults to `DefaultDataProcessorFactory`
- takes optional argument `$dataAccessProviderFactory`:
  - must implement `PhpAnonymizer\Anonymizer\DataAccess\Provider\Factory\DataAccessProviderFactoryInterface`
  - defaults to `DefaultDataAccessProviderFactory`
- takes optional argument `$dataGenerationProviderFactory`:
  - must implement `PhpAnonymizer\Anonymizer\DataGeneration\Provider\Factory\DataGenerationProviderFactoryInterface`
  - defaults to `DefaultDataGenerationProviderFactory`
- takes optional argument `$dataEncodingProvider`:
  - must implement `PhpAnonymizer\Anonymizer\DataEncoding\Provider\DataEncodingProviderInterface`
  - defaults to `DefaultDataEncodingProvider`
- takes optional argument `$dependencyChecker`:
  - must implement `PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface`
  - defaults to `DefaultDependencyChecker`
 - takes optional argument `$nodeMapperFactory`:
   - must implement `PhpAnonymizer\Anonymizer\Mapper\Node\Factory\NodeMapperFactoryInterface`
   - defaults to `DefaultNodeMapperFactory`

```php
// examples/05_02_builder_setup.php

$builder = new AnonymizerBuilder(
    nodeParserFactory: new DefaultNodeParserFactory(),
    ruleSetParserFactory: new DefaultRuleSetParserFactory(),
    dataProcessorFactory: new DefaultDataProcessorFactory(),
    dataAccessProviderFactory: new DefaultDataAccessProviderFactory(),
    dataGenerationProviderFactory: new DefaultDataGenerationProviderFactory(),
    dataEncodingProvider: new DefaultDataEncodingProvider(),
    nodeMapperFactory: new DefaultNodeMapperFactory(),
);
```

#### 05.02.02 Setting Defaults

Sets default values for the builder:
- nodeParserType: `simple`
- nodeMapperType: `default`
- ruleSetParserType: `default`
- dataProcessorType: `default`
- dataAccessProviderType: `default`
- dataGenerationProviderType: `default`
- faker: `false`

```php
// examples/05_02_builder_setup.php

$builder->withDefaults();
```

#### 05.02.03 Setting NodeParser

Two options to set node parser:
- via instance:
  - must implement `PhpAnonymizer\Anonymizer\Parser\Node\NodeParserInterface`
- via type:
  - must be a string
  - will be resolved from node parser factory (set in [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder))
- using one option will override the other

```php
// examples/05_02_builder_setup.php

$builder->withNodeParser(
    nodeParser: new ArrayNodeParser(),
);
$builder->withNodeParserType(
    nodeParserType: 'array',
);
```

#### 05.02.04 Setting NodeMapper

Two options to set node mapper:
- via instance:
  - must implement `PhpAnonymizer\Anonymizer\Mapper\Node\NodeMapperInterface`
- via type:
  - must be a string
  - will be resolved from node mapper factory (set in [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder))
  - using one option will override the other

```php
// examples/05_02_builder_setup.php

$builder->withNodeMapper(
    nodeMapper: new DefaultNodeMapper(),
);
$builder->withNodeMapperType(
    nodeMapperType: 'default',
);
```

#### 05.02.05 Setting DataAccessProvider

Two options to set data access provider:
- via instance:
  - must implement `PhpAnonymizer\Anonymizer\DataAccess\Provider\DataAccessProviderInterface`
- via type:
  - must be a string
  - will be resolved from data access provider factory (set in [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder))
  - using one option will override the other

```php
// examples/05_02_builder_setup.php

$builder->withRuleSetParser(
    ruleSetParser: new ArrayRuleSetParser(),
);

$builder->withRuleSetParserType(
    ruleSetParserType: 'default',
);
```

#### 05.02.06 Setting DataGenerationProvider

Two options to set data access provider:
- via instance:
    - must implement `PhpAnonymizer\Anonymizer\DataGeneration\Provider\DataGenerationProviderInterface`
- via type:
    - must be a string
    - will be resolved from data access provider factory (set in [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder))
    - using one option will override the other

```php
// examples/05_02_builder_setup.php

$dataGeneratorProvider = new DefaultDataGeneratorProvider(
    [
        new StarMaskedStringGenerator(),
    ],
);

$builder->withDataGenerationProvider(
    dataGenerator: $dataGeneratorProvider,
);

$builder->withDataGenerationProviderType(
    dataGeneratorType: 'default',
);
```

#### 05.02.07 Enabling Faker

Enable or disable via `bool` value.

```php
// examples/05_02_builder_setup.php

$builder->withFaker(true);
```

#### 05.02.08 Setting Custom Faker

Build custom `Faker` instance and inject into builder.

```php
// examples/05_02_builder_setup.php

$faker = Factory::create('de_DE');
$builder->withCustomFaker(
    faker: $faker,
);
```

#### 05.02.09 Setting Faker Seed

Set seed as `string`.

```php
// examples/05_02_builder_setup.php

$builder->withFakerSeed('my-faker-seed');
```

#### 05.02.10 Setting Rule Loader

Four options to set rules:
- via instance:
  - must implement `PhpAnonymizer\Anonymizer\RuleLoader\RuleLoaderInterface`
- via `array` directly:
  - must be `string` to existing file
- via json file path:
  - must be string to existing file
  - for the loader to work, the json php extension is required
- via yaml file path:
  - must be string to existing file
  - for the loader to work, the yaml php extension is required

In any case the given rule format must match the configured ruleset parser's input format.

```php
// examples/05_02_builder_setup.php

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
```

#### 05.02.11 Setting RuleSetParser

Two options to set rule set parser:
- via instance:
  - must implement `PhpAnonymizer\Anonymizer\Parser\RuleSet\RuleSetParserInterface`
  - will ignore dependencies set in builder
- via type:
  - must be a string
  - will be resolved from RuleSetParserFactory (set in [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder))
  - will use NodeParser from (set in [05.02.03 Setting NodeParser](#050203-setting-nodeparser))
- using one option will override the other

```php
// examples/05_02_builder_setup.php

$builder->withRuleSetParser(
    ruleSetParser: new ArrayRuleSetParser(),
);
$builder->withRuleSetParserType(
    ruleSetParserType: 'array',
);
```

#### 05.02.12 Setting DataProcessor

Two options to set data processor:
- via instance:
  - must implement `PhpAnonymizer\Anonymizer\Processor\DataProcessorInterface`
  - will ignore dependencies set in builder
- via type:
  - must be a string
  - will be resolved from DataProcessorFactory (set in [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder))
  - will use DataAccessProvider from (set in [05.02.05 Setting DataAccessProvider](#050205-setting-dataaccessprovider))
  - will use DataGenerationProvider from (set in [05.02.06 Setting DataGenerationProvider](#050206-setting-datagenerationprovider))
  - will use DataEncodingProvider from (set in [05.02.01 Creating AnonymizerBuilder](#050201-creating-anonymizerbuilder))
- using one option will override the other

```php
// examples/05_02_builder_setup.php

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
```
