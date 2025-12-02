<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Integration;

use PhpAnonymizer\Anonymizer\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\DataEncoder;
use PhpAnonymizer\Anonymizer\Enum\NodeParser;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Address;
use PhpAnonymizer\Anonymizer\Test\Helper\Model\Data;
use PHPUnit\Framework\TestCase;
use stdClass;

class AnonymizerTest extends TestCase
{
    public function testCanSubstituteDataInArray(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->build();

        $anonymizer->registerRuleSet(
            name: 'address',
            definitions: [
                'address.name',
            ],
        );

        $data = [
            'address' => [
                'name' => 'John Doe',
                'city' => 'New York',
            ],
        ];

        $processedData = $anonymizer->run('address', $data);

        self::assertSame('********', $processedData['address']['name']);
        self::assertSame('New York', $processedData['address']['city']);
    }

    public function testCanSubstituteDataInObjectViaProperty(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->build();

        $anonymizer->registerRuleSet(
            name: 'address',
            definitions: [
                'address.name',
            ],
            defaultDataAccess: DataAccess::PROPERTY->value,
        );

        $address = new stdClass();
        $address->name = 'John Doe';
        $address->city = 'New York';

        $data = new stdClass();
        $data->address = $address;

        $processedData = $anonymizer->run('address', $data);

        self::assertSame('********', $processedData->address->name);
        self::assertSame('New York', $processedData->address->city);
    }

    public function testcanSubstituteDataInObjectViaPropertyWithFakerData(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->withFaker(true)
            ->withFakerSeed('test')
            ->withNodeParserType(NodeParser::COMPLEX->value)
            ->build();

        $anonymizer->registerRuleSet(
            name: 'address',
            definitions: [
                'address.firstName[#firstName]',
                'address.lastName[#lastName]',
            ],
        );

        $data = [
            'address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'city' => 'New York',
            ],
        ];

        $processedData = $anonymizer->run('address', $data);

        self::assertSame('Marley', $processedData['address']['firstName']);
        self::assertSame('Kerluke', $processedData['address']['lastName']);
        self::assertSame('New York', $processedData['address']['city']);
    }

    public function testCanSubstituteDataInJsonDocument(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->withFaker(true)
            ->withFakerSeed('test')
            ->withNodeParserType(NodeParser::COMPLEX->value)
            ->build();

        $anonymizer->registerRuleSet(
            name: 'address',
            definitions: [
                'address[property].firstName[property#firstName]',
                'address[property].lastName[property#lastName]',
            ],
        );

        $data = '{"address":{"firstName":"John","lastName":"Doe","city":"New York"}}';
        $processedData = $anonymizer->run('address', $data, DataEncoder::JSON->value);

        self::assertSame('{"address":{"firstName":"Marley","lastName":"Kerluke","city":"New York"}}', $processedData);
    }

    public function testCanSubstituteValuesInFilteredFieldsOnly(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->withFaker(true)
            ->withFakerSeed('test')
            ->withNodeParserType(NodeParser::COMPLEX->value)
            ->build();

        $anonymizer->registerRuleSet(
            name: 'properties',
            definitions: [
                '[]properties.value[%name/firstName]',
                '[]properties.value[%name/lastName]',
            ],
        );

        $data = [
            'properties' => [
                [
                    'name' => 'firstName',
                    'value' => 'John',
                ],
                [
                    'name' => 'lastName',
                    'value' => 'Doe',
                ],
                [
                    'name' => 'city',
                    'value' => 'New York',
                ],
            ],
        ];

        $processedData = $anonymizer->run('properties', $data);

        self::assertSame('****', $processedData['properties'][0]['value']);
        self::assertSame('***', $processedData['properties'][1]['value']);
        self::assertSame('New York', $processedData['properties'][2]['value']);
    }

    public function testCanSubstituteDataInObjectViaSetterMethod(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->build();

        $anonymizer->registerRuleSet(
            name: 'address',
            definitions: [
                'address.name',
            ],
            defaultDataAccess: DataAccess::SETTER->value,
        );

        $address = new Address(
            name: 'John Doe',
            city: 'New York',
        );

        $data = new Data(
            address: $address,
        );

        $processedData = $anonymizer->run('address', $data);

        self::assertSame('********', $processedData->getAddress()->getName());
        self::assertSame('New York', $processedData->getAddress()->getCity());
    }

    public function testCanSubstituteDataInListOfArrays(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->build();

        $anonymizer->registerRuleSet(
            name: 'address',
            definitions: [
                '[]addresses.name',
            ],
        );

        $data = [
            'addresses' => [
                ['name' => 'John Doe', 'city' => 'New York'],
                ['name' => 'Jane Doe', 'city' => 'Los Angeles'],
            ],
        ];

        $processedData = $anonymizer->run('address', $data);

        self::assertSame('********', $processedData['addresses'][0]['name']);
        self::assertSame('New York', $processedData['addresses'][0]['city']);

        self::assertSame('********', $processedData['addresses'][1]['name']);
        self::assertSame('Los Angeles', $processedData['addresses'][1]['city']);
    }

    public function testCanSubstitueDataInMixedTypeHierarchy(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->withNodeParserType(NodeParser::COMPLEX->value)
            ->build();

        $anonymizer->registerRuleSet(
            name: 'address',
            definitions: [
                'data.[]addresses[property].name[setter]',
            ],
        );

        $addresses = [
            new Address(name: 'John Doe', city: 'New York'),
            new Address(name: 'Jane Doe', city: 'Los Angeles'),
        ];

        $data = new class ($addresses) {
            /**
             * @param Address[] $addresses
             */
            public function __construct(public array $addresses)
            {
            }
        };

        $processedData = $anonymizer->run('address', ['data' => $data]);

        self::assertSame('********', $processedData['data']->addresses[0]->getName());
        self::assertSame('New York', $processedData['data']->addresses[0]->getCity());

        self::assertSame('********', $processedData['data']->addresses[1]->getName());
        self::assertSame('Los Angeles', $processedData['data']->addresses[1]->getCity());
    }

    public function testCanSubstituteDataByUsingAutoDetectionOfObjectTypes(): void
    {
        $anonymizer = (new AnonymizerBuilder())
            ->withDefaults()
            ->build();

        $anonymizer->registerRuleSet(
            name: 'address',
            definitions: [
                'data.[]addresses.name',
            ],
            defaultDataAccess: DataAccess::AUTODETECT->value,
        );

        $addresses = [
            new Address(name: 'John Doe', city: 'New York'),
            new Address(name: 'Jane Doe', city: 'Los Angeles'),
        ];

        $data = new class ($addresses) {
            /**
             * @param Address[] $addresses
             */
            public function __construct(public array $addresses)
            {
            }
        };

        $processedData = $anonymizer->run('address', ['data' => $data]);

        self::assertSame('********', $processedData['data']->addresses[0]->getName());
        self::assertSame('New York', $processedData['data']->addresses[0]->getCity());

        self::assertSame('********', $processedData['data']->addresses[1]->getName());
        self::assertSame('Los Angeles', $processedData['data']->addresses[1]->getCity());
    }
}
