<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleDefinition;

final readonly class ProfileRegistry implements ProfileProviderInterface
{
    /** @var array<string, ProfileDefinition> */
    private array $profiles;

    /** @param iterable<ProfileDefinition> $profiles */
    public function __construct(iterable $profiles)
    {
        $registered = [];
        foreach ($profiles as $profile) {
            $name = $profile->name();
            if (isset($registered[$name])) {
                throw new InvalidArgumentException(sprintf('Profile "%s" is registered more than once.', $name));
            }

            $registered[$name] = $profile;
        }

        $this->profiles = $registered;
    }

    public function get(string $name): ?ProfileDefinition
    {
        return $this->profiles[$name] ?? null;
    }

    /** @return iterable<ProfileDefinition> */
    #[Override]
    public function profiles(): iterable
    {
        yield from $this->profiles;
    }

    public function validate(ReplacementStrategyRegistry $strategies, PayloadCodecRegistry $codecs): void
    {
        foreach ($this->profiles as $profile) {
            $this->rules($profile->name(), $profile->rules(), $strategies, $codecs);
        }

        foreach ($this->profiles as $profile) {
            $this->cycle($profile->name(), [], []);
        }
    }

    /** @param list<RuleDefinition> $rules */
    private function rules(string $profile, array $rules, ReplacementStrategyRegistry $strategies, PayloadCodecRegistry $codecs): void
    {
        foreach ($rules as $rule) {
            if ($rule->strategy() !== null && !$strategies->get($rule->strategy()) instanceof ReplacementStrategyInterface) {
                throw new InvalidArgumentException(sprintf('Profile "%s" references unknown replacement strategy "%s".', $profile, $rule->strategy()));
            }
            $embedded = $rule->embedded();
            if ($embedded !== null) {
                if (!$codecs->get($embedded->codec()) instanceof PayloadCodecInterface) {
                    throw new InvalidArgumentException(sprintf('Profile "%s" references unknown payload codec "%s".', $profile, $embedded->codec()));
                }
                if (!$this->get($embedded->profile()) instanceof ProfileDefinition) {
                    throw new InvalidArgumentException(sprintf('Profile "%s" references unknown embedded profile "%s".', $profile, $embedded->profile()));
                }
            }
            $this->rules($profile, $rule->children(), $strategies, $codecs);
            $this->rules($profile, $rule->eachRules() ?? [], $strategies, $codecs);
        }
    }

    /**
     * @param list<string> $path
     * @param array<string, true> $visited
     */
    private function cycle(string $profile, array $path, array $visited): void
    {
        if (isset($visited[$profile])) {
            $cycle = [...$path, $profile];
            throw new InvalidArgumentException(sprintf('Embedded profile reference cycle detected: %s.', implode(' -> ', $cycle)));
        }
        $visited[$profile] = true;
        $profileDefinition = $this->profiles[$profile];
        foreach ($this->embeddedProfiles($profileDefinition->rules()) as $embedded) {
            $this->cycle($embedded, [...$path, $profile], $visited);
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
            $profiles = [...$profiles, ...$this->embeddedProfiles($rule->children())];
            $profiles = [...$profiles, ...$this->embeddedProfiles($rule->eachRules() ?? [])];
        }

        return $profiles;
    }
}
