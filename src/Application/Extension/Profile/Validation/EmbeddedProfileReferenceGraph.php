<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;

// @internal
final readonly class EmbeddedProfileReferenceGraph
{
    /** @var array<string, list<string>> */
    private array $edges;

    /**
     * @param iterable<ProfileDefinition> $profiles
     */
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
            $cycle = $this->visit($profile, [], []);
            if ($cycle !== null) {
                throw new InvalidArgumentException(sprintf('Embedded profile reference cycle detected: %s.', implode(' -> ', $cycle)));
            }
        }
    }

    /**
     * @param list<string> $path
     * @param array<string, true> $visited
     *
     * @return null|list<string>
     */
    private function visit(string $profile, array $path, array $visited): ?array
    {
        $cycle = null;
        if (isset($visited[$profile])) {
            $cycle = [...$path, $profile];
        } else {
            $visited[$profile] = true;
            foreach ($this->edges[$profile] ?? [] as $next) {
                $cycle = $this->visit($next, [...$path, $profile], $visited);
                if ($cycle !== null) {
                    break;
                }
            }
        }

        return $cycle;
    }

    /**
     * @param list<RuleDefinition> $rules
     *
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
