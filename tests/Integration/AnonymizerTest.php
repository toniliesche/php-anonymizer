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

        $this->assertSame('********', $processedData['address']['name']);
        $this->assertSame('New York', $processedData['address']['city']);
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

        $this->assertSame('********', $processedData->address->name);
        $this->assertSame('New York', $processedData->address->city);
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

        $this->assertSame('Marley', $processedData['address']['firstName']);
        $this->assertSame('Kerluke', $processedData['address']['lastName']);
        $this->assertSame('New York', $processedData['address']['city']);
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

        $this->assertSame('{"address":{"firstName":"Marley","lastName":"Kerluke","city":"New York"}}', $processedData);
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

        $this->assertSame('********', $processedData->getAddress()->getName());
        $this->assertSame('New York', $processedData->getAddress()->getCity());
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

        $this->assertSame('********', $processedData['addresses'][0]['name']);
        $this->assertSame('New York', $processedData['addresses'][0]['city']);

        $this->assertSame('********', $processedData['addresses'][1]['name']);
        $this->assertSame('Los Angeles', $processedData['addresses'][1]['city']);
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

        $this->assertSame('********', $processedData['data']->addresses[0]->getName());
        $this->assertSame('New York', $processedData['data']->addresses[0]->getCity());

        $this->assertSame('********', $processedData['data']->addresses[1]->getName());
        $this->assertSame('Los Angeles', $processedData['data']->addresses[1]->getCity());
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

        $this->assertSame('********', $processedData['data']->addresses[0]->getName());
        $this->assertSame('New York', $processedData['data']->addresses[0]->getCity());

        $this->assertSame('********', $processedData['data']->addresses[1]->getName());
        $this->assertSame('Los Angeles', $processedData['data']->addresses[1]->getCity());
    }
}
