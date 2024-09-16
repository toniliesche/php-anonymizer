<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Unit;

use PhpAnonymizer\Anonymizer\Anonymizer;
use PhpAnonymizer\Anonymizer\DataAccess\Provider\DefaultDataAccessProvider;
use PhpAnonymizer\Anonymizer\DataEncoding\Provider\DefaultDataEncodingProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\Provider\DefaultDataGeneratorProvider;
use PhpAnonymizer\Anonymizer\DataGeneration\StarMaskedStringGenerator;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Parser\RuleSet\DefaultRuleSetParser;
use PhpAnonymizer\Anonymizer\Processor\DefaultDataProcessor;
use PHPUnit\Framework\TestCase;

class AnonymizerTest extends TestCase
{
    public function testWillFailOnRegisteringingDefaultAsDataAccessForRuleSet(): void
    {
        $anonymizer = new Anonymizer(
            new DefaultRuleSetParser(),
            new DefaultDataProcessor(
                new DefaultDataAccessProvider(),
                new DefaultDataGeneratorProvider(
                    [
                        new StarMaskedStringGenerator(),
                    ],
                ),
                new DefaultDataEncodingProvider(),
            ),
        );

        $this->expectException(InvalidArgumentException::class);
        $anonymizer->registerRuleSet(
            'address',
            [
                'address.name',
            ],
            DataAccess::DEFAULT->value,
        );
    }
}
