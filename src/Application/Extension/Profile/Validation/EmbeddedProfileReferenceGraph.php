<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

final readonly class EmbeddedProfileReferenceGraph
{
    /** @var array<string, list<string>> */
    private array $edges;

    /** @param iterable<ProfileDefinition> $profiles */
    public function __construct(iterable $profiles)
    {
        /** @var array<string, list<string>> $edges */
        $edges = [];
        foreach ($profiles as $profile) {
            $edges[$profile->name()] = $this->embeddedProfiles($profile->rules());
        }
        $this->edges = $edges;
    }

    public function assertAcyclic(): void
    {
        foreach ($this->edges as $profile => $_) {
            $this->visit($profile, [], []);
        }
    }

    /**
     * @param list<string> $path
     * @param array<string, true> $visited
     */
    private function visit(string $profile, array $path, array $visited): void
    {
        if (isset($visited[$profile])) {
            throw new InvalidArgumentException(sprintf('Embedded profile reference cycle detected: %s.', implode(' -> ', [...$path, $profile])));
        }
        $visited[$profile] = true;
        foreach ($this->edges[$profile] ?? [] as $next) {
            $this->visit($next, [...$path, $profile], $visited);
        }
    }

    /**
     * @param list<RuleDefinition> $rules
     * @return list<string>
     */
    private function embeddedProfiles(array $rules): array
    {
        $profiles = [];
        foreach ($rules as $rule) {
            if ($rule->embedded() !== null) {
                $profiles[] = $rule->embedded()->profile();
            }
            $profiles = [...$profiles, ...$this->embeddedProfiles($rule->children()), ...$this->embeddedProfiles($rule->eachRules() ?? [])];
        }
        return $profiles;
    }
}
