<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use function Safe\preg_match;

// @codeCoverageIgnoreStart
/**
 * @internal
 */
// @phpstan-ignore complexity.classLike (composition validation is intentionally explicit)
final class ProfileCompositionCompiler
{
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
    // @phpstan-ignore complexity.functionLike (composition branches are intentionally explicit)
    private function expandList(string $profile, array $entries, array $fragments, array $stack, string $path, bool $direct): array
    {
        $result = [];

        /** @var array<string, array{index: int, direct: bool}> $fields */
        $fields = [];

        foreach ($entries as $index => $entry) {
            if (!is_array($entry)) {
                if (str_contains($path, '.children') || str_contains($path, '.each')) {
                    throw new InvalidProfileDefinition(sprintf('nested rule %s must be a map', $index));
                }

                throw InvalidProfileDefinition::at($profile, sprintf('%s[%s] must be a map', $path, $index));
            }

            if (array_key_exists('use', $entry)) {
                if (array_keys($entry) !== ['use'] || !is_string($entry['use']) || $entry['use'] === '') {
                    throw InvalidProfileDefinition::at($profile, sprintf('%s[%s] must contain only a non-empty use name', $path, $index));
                }
                $name = $entry['use'];
                if (!array_key_exists($name, $fragments)) {
                    throw InvalidProfileDefinition::at($profile, sprintf('%s[%s] references missing fragment "%s"', $path, $index, $name));
                }
                if (in_array($name, $stack, true)) {
                    throw InvalidProfileDefinition::at($profile, sprintf('%s[%s] creates fragment cycle: %s -> %s', $path, $index, implode(' -> ', [...$stack, $name]), $name));
                }
                $expanded = $this->expandList($profile, $fragments[$name], $fragments, [...$stack, $name], $path . '.use(' . $name . ')', false);

                /** @var array<string, mixed> $rule */
                foreach ($expanded as $rule) {
                    $this->append($result, $fields, $rule, false);
                }

                continue;
            }

            /** @var array<string, mixed> $rule */
            $rule = $entry;
            if (array_key_exists('children', $rule)) {
                if (is_array($rule['children']) && array_is_list($rule['children'])) {
                    $rule['children'] = $this->expandList($profile, $rule['children'], $fragments, $stack, $path . '[' . $index . '].children', $direct);
                }
            } elseif (array_key_exists('each', $rule) && is_array($rule['each']) && array_is_list($rule['each'])) {
                $rule['each'] = $this->expandList($profile, $rule['each'], $fragments, $stack, $path . '[' . $index . '].each', $direct);
            }
            $this->append($result, $fields, $rule, $direct);
        }

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $result
     * @param array<string, array{index: int, direct: bool}> $fields
     * @param array<string, mixed> $rule
     */
    private function append(array &$result, array &$fields, array $rule, bool $direct): void
    {
        $field = $rule['field'] ?? null;
        if (!is_string($field) || $field === '') {
            $result[] = $rule;

            return;
        }

        if (!array_key_exists($field, $fields)) {
            $fields[$field] = ['index' => count($result), 'direct' => $direct];
            $result[] = $rule;

            return;
        }

        $existing = $fields[$field];
        if ($direct && !$existing['direct']) {
            $result[$existing['index']] = $rule;
            $fields[$field]['direct'] = true;

            return;
        }

        // Preserve duplicate direct rules for the existing RuleDefinition/
        // ProfileDefinition diagnostics, while fragment collisions are
        // intentionally resolved by first declaration.
        if ($direct) {
            $result[] = $rule;
        }
    }
}
// @codeCoverageIgnoreEnd
