<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Enum;

enum DataField: string
{
    case BUILDING_NUMBER = 'buildingNumber';
    case EMAIL = 'email';
    case FIRST_NAME = 'firstName';
    case LAST_NAME = 'lastName';
    case NAME = 'name';
    case STREET = 'street';
    case STREET_NUMBER = 'streetNumber';
    case HOUSE_NUMBER = 'houseNumber';
    case CITY = 'city';
    case POSTCODE = 'postcode';
    case ZIP = 'zip';
    case COUNTRY = 'country';
    case COMPANY = 'company';
    case USERNAME = 'username';
    case PASSWORD = 'password';
}
