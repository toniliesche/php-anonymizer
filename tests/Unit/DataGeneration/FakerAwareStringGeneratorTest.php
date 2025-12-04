<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataGeneration;

use Faker\Factory;
use PhpAnonymizer\Anonymizer\DataGeneration\FakerAwareStringGenerator;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataField;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class FakerAwareStringGeneratorTest extends TestCase
{
    public function testCanVerifySupportOfStrings(): void
    {
        $dataGenerator = new FakerAwareStringGenerator();

        self::assertFalse($dataGenerator->supports('Hello World', null));
        self::assertFalse($dataGenerator->supports('Hello World', 'welcome'));
        self::assertFalse($dataGenerator->supports('Hello World', DataField::NAME->value));

        $faker = Factory::create();
        $dataGenerator->setFaker($faker);

        self::assertTrue($dataGenerator->supports('Hello World', DataField::NAME->value));
    }

    public function testCanVerifyNonSupportOfObjects(): void
    {
        $dataGenerator = new FakerAwareStringGenerator();

        self::assertFalse($dataGenerator->supports(new stdClass(), null));
    }

    public function testCanVerifySupportOfStringViaFallback(): void
    {
        $faker = Factory::create();
        $dataGenerator = new FakerAwareStringGenerator(new StarMaskedStringGenerator());
        $dataGenerator->setFaker($faker);

        self::assertTrue($dataGenerator->supports('Hello World', null));
    }

    public function testCanGenerateFakeDataViaFaker(): void
    {
        $faker = Factory::create('de_DE');
        $faker->seed(1234);

        $dataGenerator = new FakerAwareStringGenerator(new StarMaskedStringGenerator());

        $firstname = 'Max';
        $anonymizedFirstname = $dataGenerator->generate(['test'], $firstname, DataField::FIRST_NAME->value);
        self::assertSame('***', $anonymizedFirstname);

        $dataGenerator->setFaker($faker);

        $firstName = 'Max';
        $anonymizedFirstName = $dataGenerator->generate(['test'], $firstName, 'firstName');
        self::assertSame('Jenny', $anonymizedFirstName);

        $lastName = 'Mustermann';
        $anonymizedLastName = $dataGenerator->generate(['test'], $lastName, 'lastName');
        self::assertSame('Neubauer', $anonymizedLastName);

        $email = 'test@example.com';
        $anonymizedEmail = $dataGenerator->generate(['test'], $email, 'email');
        self::assertSame('mai.edelgard@example.net', $anonymizedEmail);

        $street = 'Musterstraße';
        $anonymizedStreet = $dataGenerator->generate(['test'], $street, 'street');
        self::assertSame('Nico-Christ-Weg', $anonymizedStreet);

        $streetNumber = '42';
        $anonymizedStreetNumber = $dataGenerator->generate(['test'], $streetNumber, 'streetNumber');
        self::assertSame('82a', $anonymizedStreetNumber);

        $city = 'Musterstadt';
        $anonymizedCity = $dataGenerator->generate(['test'], $city, 'city');
        self::assertSame('Obertshausen', $anonymizedCity);

        $zip = '12345';
        $anonymizedZip = $dataGenerator->generate(['test'], $zip, 'zip');
        self::assertSame('49546', $anonymizedZip);

        $country = 'Deutschland';
        $anonymizedCountry = $dataGenerator->generate(['test'], $country, 'country');
        self::assertSame('Malaysia', $anonymizedCountry);

        $company = 'Musterfirma';
        $anonymizedCompany = $dataGenerator->generate(['test'], $company, 'company');
        self::assertSame('Heinrich Günther GmbH & Co. OHG', $anonymizedCompany);

        $username = 'max.mustermann';
        $anonymizedUsername = $dataGenerator->generate(['test'], $username, 'username');
        self::assertSame('jost94', $anonymizedUsername);

        $password = 'password';
        $anonymizedPassword = $dataGenerator->generate(['test'], $password, 'password');
        self::assertSame('J"}6<,h]fZt(!', $anonymizedPassword);

        $name = 'Max Mustermann';
        $anonymizedName = $dataGenerator->generate(['test'], $name, 'name');
        self::assertSame('Herr Prof. Dr. Arndt Miller', $anonymizedName);

        $welcome = 'Hello World';
        $anonymizedWelcome = $dataGenerator->generate(['test'], $welcome, 'welcome');
        self::assertSame('***********', $anonymizedWelcome);

        $welcome = 'Hello World';
        $anonymizedWelcome = $dataGenerator->generate(['test'], $welcome, null);
        self::assertSame('***********', $anonymizedWelcome);
    }

    public function testWillFailOnGenerateFakeDataViaFakerOnNonStrings(): void
    {
        $faker = Factory::create('de_DE');
        $faker->seed(1234);

        $dataGenerator = new FakerAwareStringGenerator(new StarMaskedStringGenerator());

        $this->expectException(InvalidObjectTypeException::class);

        /** @phpstan-ignore-next-line  */
        $dataGenerator->generate(['test'], new stdClass(), null);
    }
}
