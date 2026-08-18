<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\ProfileMode;

final readonly class ArrayTraversal
{
    private RedactArrayTraversal $redactor;
    private PreserveArrayTraversal $preserver;
    private EmbeddedPayloadTraversal $embedded;

    public function __construct(
        private ProfileRegistry $profiles,
        ReplacementStrategyRegistry $strategies,
        PayloadCodecRegistry $codecs,
    ) {
        $this->embedded = new EmbeddedPayloadTraversal($codecs, $this);
        $this->redactor = new RedactArrayTraversal($strategies, $this->embedded);
        $this->preserver = new PreserveArrayTraversal($strategies, $this->embedded);
    }

    /** @param array<string, mixed> $data */
    public function apply(string $profile, array $data): AnonymizationResult
    {
        $definition = $this->profiles->get($profile);
        if (!$definition instanceof ProfileDefinition) {
            throw ProfileNotFound::named($profile);
        }

        /** @var array<string, mixed> $result */
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
