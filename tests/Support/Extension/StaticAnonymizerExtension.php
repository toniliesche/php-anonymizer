<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Support\Extension;

use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Application\Extension\AnonymizerExtensionInterface;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

final readonly class StaticAnonymizerExtension implements AnonymizerExtensionInterface
{
    public function __construct(private ProfileDefinition $profile)
    {
    }

    #[Override]
    public function configure(AnonymizerBuilder $builder): AnonymizerBuilder
    {
        return $builder->withProfile($this->profile);
    }
}
