<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Traits;

use Faker\Generator;
use PhpAnonymizer\Anonymizer\Dependency\DependencyCheckerInterface;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\MissingPlatformRequirementsException;

trait FakerAwareTrait
{
    protected Generator $faker;

    protected DependencyCheckerInterface $dependencyChecker;

    /**
     * @param Generator $faker
     */
    public function setFaker(mixed $faker): void
    {
        if (!$this->dependencyChecker->libraryIsInstalled('fakerphp/faker')) {
            throw new MissingPlatformRequirementsException('Faker library is not installed');
        }

        if (!$faker instanceof Generator) {
            throw new InvalidArgumentException('Faker object must be an instance of Faker Generator');
        }

        $this->faker = $faker;
    }
}
