<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use function Safe\preg_match;

// @codeCoverageIgnoreStart
/**
 * @internal
 */
final readonly class ProfileCompositionCompiler
{
    private ProfileRuleAppender $appender;

    public function __construct()
    {
        $this->appender = new ProfileRuleAppender();
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array{rules: list<array<string, mixed>>, fragments: list<string>}
     */
    public function compile(string $profile, array $definition): array
    {
        $fragments = $this->fragments($profile, $definition['fragments'] ?? null);
        $fragmentNames = array_keys($fragments);

        /** @var list<mixed> $rules */
        $rules = $definition['rules'] ?? [];
        $expanded = $this->expandList($profile, $rules, $fragments, [], 'rules', true);

        return ['rules' => $expanded, 'fragments' => $fragmentNames];
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function fragments(string $profile, mixed $value): array
    {
        if ($value === null) {
            return [];
        }
        if (!is_array($value) || !array_is_list($value) || $value === []) {
            throw InvalidProfileDefinition::forProfile($profile, 'fragments must be a non-empty ordered list when provided');
        }

        $fragments = [];
        foreach ($value as $index => $fragment) {
            if (!is_array($fragment) || array_diff(array_keys($fragment), ['name', 'rules']) !== [] || !array_key_exists('name', $fragment) || !array_key_exists('rules', $fragment)) {
                throw InvalidProfileDefinition::at($profile, sprintf('fragments[%s] must contain exactly name and rules', $index));
            }
            $name = $fragment['name'];
            if (!is_string($name) || preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $name) !== 1) {
                throw InvalidProfileDefinition::at($profile, sprintf('fragments[%s].name is invalid', $index));
            }
            if (array_key_exists($name, $fragments)) {
                throw InvalidProfileDefinition::at($profile, sprintf('fragments[%s].name "%s" is duplicated', $index, $name));
            }
            if (!is_array($fragment['rules']) || !array_is_list($fragment['rules']) || $fragment['rules'] === []) {
                throw InvalidProfileDefinition::at($profile, sprintf('fragments[%s].rules must be a non-empty list', $index));
            }
            $fragments[$name] = $fragment['rules'];
        }

        return $fragments;
    }

    /**
     * @param list<mixed> $entries
     * @param array<string, list<mixed>> $fragments
     * @param list<string> $stack
     *
     * @return list<array<string, mixed>>
     */
    private function expandList(string $profile, array $entries, array $fragments, array $stack, string $path, bool $direct): array
    {
        $result = [];

        /** @var array<string, array{index: int, direct: bool}> $fields */
        $fields = [];

        foreach ($entries as $index => $entry) {
            $entryIsFragment = is_array($entry) && array_key_exists('use', $entry);
            foreach ($this->expandEntry($profile, $entry, $fragments, $stack, $path, $index, $direct) as $rule) {
                $this->appender->append($result, $fields, $rule, $direct && !$entryIsFragment);
            }
        }

        return $result;
    }

    /**
     * @param array<string, list<mixed>> $fragments
     * @param list<string> $stack
     *
     * @return list<array<string, mixed>>
     */
    private function expandEntry(string $profile, mixed $entry, array $fragments, array $stack, string $path, int|string $index, bool $direct): array
    {
        if (!is_array($entry)) {
            $this->invalidEntry($profile, $path, $index);
        }

        /** @var array<string, mixed> $entry */
        if (array_key_exists('use', $entry)) {
            return $this->expandFragment($profile, $entry, $fragments, $stack, $path, $index);
        }

        return [$this->expandNested($entry, $profile, $fragments, $stack, $path, $index, $direct)];
    }

    /** @param array<string, mixed> $rule
     * @param array<string, list<mixed>> $fragments
     * @param list<string> $stack
     *
     * @return array<string, mixed> */
    private function expandNested(array $rule, string $profile, array $fragments, array $stack, string $path, int|string $index, bool $direct): array
    {
        if (array_key_exists('children', $rule) && is_array($rule['children']) && array_is_list($rule['children'])) {
            $rule['children'] = $this->expandList($profile, $rule['children'], $fragments, $stack, $path . '[' . $index . '].children', $direct);
        } elseif (array_key_exists('each', $rule) && is_array($rule['each']) && array_is_list($rule['each'])) {
            $rule['each'] = $this->expandList($profile, $rule['each'], $fragments, $stack, $path . '[' . $index . '].each', $direct);
        }

        return $rule;
    }

    private function invalidEntry(string $profile, string $path, int|string $index): never
    {
        if (str_contains($path, '.children') || str_contains($path, '.each')) {
            throw new InvalidProfileDefinition(sprintf('nested rule %s must be a map', $index));
        }

        throw InvalidProfileDefinition::at($profile, sprintf('%s[%s] must be a map', $path, $index));
    }

    /**
     * @param array<string, mixed> $entry
     * @param array<string, list<mixed>> $fragments
     * @param list<string> $stack
     *
     * @return list<array<string, mixed>>
     */
    private function expandFragment(string $profile, array $entry, array $fragments, array $stack, string $path, int|string $index): array
    {
        if (array_keys($entry) !== ['use'] || !is_string($entry['use'] ?? null) || $entry['use'] === '') {
            throw InvalidProfileDefinition::at($profile, sprintf('%s[%s] must contain only a non-empty use name', $path, $index));
        }
        $name = $entry['use'];
        if (!array_key_exists($name, $fragments)) {
            throw InvalidProfileDefinition::at($profile, sprintf('%s[%s] references missing fragment "%s"', $path, $index, $name));
        }
        if (in_array($name, $stack, true)) {
            throw InvalidProfileDefinition::at($profile, sprintf('%s[%s] creates fragment cycle: %s -> %s', $path, $index, implode(' -> ', [...$stack, $name]), $name));
        }

        return $this->expandList($profile, $fragments[$name], $fragments, [...$stack, $name], $path . '.use(' . $name . ')', false);
    }
}
// @codeCoverageIgnoreEnd
