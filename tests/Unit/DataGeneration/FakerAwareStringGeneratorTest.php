<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit\DataGeneration;

use Faker\Factory;
use PhpAnonymizer\Anonymizer\DataGeneration\FakerAwareStringGenerator;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataField;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use stdClass;

final class FakerAwareStringGeneratorTest extends TestCase
{
    use MatchesSnapshots;

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
        $this->assertMatchesSnapshot($anonymizedFirstName);

        $lastName = 'Mustermann';
        $anonymizedLastName = $dataGenerator->generate(['test'], $lastName, 'lastName');
        $this->assertMatchesSnapshot($anonymizedLastName);

        $email = 'test@example.com';
        $anonymizedEmail = $dataGenerator->generate(['test'], $email, 'email');
        $this->assertMatchesSnapshot($anonymizedEmail);

        $street = 'Musterstraße';
        $anonymizedStreet = $dataGenerator->generate(['test'], $street, 'street');
        $this->assertMatchesSnapshot($anonymizedStreet);

        $streetNumber = '42';
        $anonymizedStreetNumber = $dataGenerator->generate(['test'], $streetNumber, 'streetNumber');
        $this->assertMatchesSnapshot($anonymizedStreetNumber);

        $city = 'Musterstadt';
        $anonymizedCity = $dataGenerator->generate(['test'], $city, 'city');
        $this->assertMatchesSnapshot($anonymizedCity);

        $zip = '12345';
        $anonymizedZip = $dataGenerator->generate(['test'], $zip, 'zip');
        $this->assertMatchesSnapshot($anonymizedZip);

        $country = 'Deutschland';
        $anonymizedCountry = $dataGenerator->generate(['test'], $country, 'country');
        $this->assertMatchesSnapshot($anonymizedCountry);

        $company = 'Musterfirma';
        $anonymizedCompany = $dataGenerator->generate(['test'], $company, 'company');
        $this->assertMatchesSnapshot($anonymizedCompany);

        $username = 'max.mustermann';
        $anonymizedUsername = $dataGenerator->generate(['test'], $username, 'username');
        $this->assertMatchesSnapshot($anonymizedUsername);

        $password = 'password';
        $anonymizedPassword = $dataGenerator->generate(['test'], $password, 'password');
        $this->assertMatchesSnapshot($anonymizedPassword);

        $name = 'Max Mustermann';
        $anonymizedName = $dataGenerator->generate(['test'], $name, 'name');
        $this->assertMatchesSnapshot($anonymizedName);

        $welcome = 'Hello World';
        $anonymizedWelcome = $dataGenerator->generate(['test'], $welcome, 'welcome');
        $this->assertMatchesSnapshot($anonymizedWelcome);

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
