<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use Override;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\ProfileMode;

final readonly class ArrayAnonymizer implements AnonymizerInterface
{
    private RedactArrayTraversal $redactor;
    private PreserveArrayTraversal $preserver;

    public function __construct(private ProfileProviderInterface $profiles)
    {
        $this->redactor = new RedactArrayTraversal();
        $this->preserver = new PreserveArrayTraversal();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[Override]
    public function anonymize(string $profile, array $data): array
    {
        return $this->anonymizeWithReport($profile, $data)->data();
    }

    /** @param array<string, mixed> $data */
    #[Override]
    public function anonymizeWithReport(string $profile, array $data): AnonymizationResult
    {
        $definition = $this->profiles->get($profile);
        if (!$definition instanceof ProfileDefinition) {
            throw ProfileNotFound::named($profile);
        }

        $result = $data;
        /** @var list<string> $anonymizedPaths */
        $anonymizedPaths = [];
        /** @var list<string> $missingPaths */
        $missingPaths = [];
        if ($definition->mode() === ProfileMode::PRESERVE) {
            $this->preserver->apply($profile, $result, $definition->rules(), $anonymizedPaths, $missingPaths);
        } else {
            $this->redactor->apply($profile, $result, $definition->rules(), $anonymizedPaths, $missingPaths);
        }

        /** @var array<string, mixed> $result */
        return new AnonymizationResult($result, $anonymizedPaths, $missingPaths);
    }
}
