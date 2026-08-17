<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application;

use Override;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleDefinition;

final readonly class ArrayAnonymizer implements AnonymizerInterface
{
    public function __construct(private ProfileProviderInterface $profiles) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    #[Override]
    public function anonymize(string $profile, array $data): array
    {
        return $this->anonymizeWithReport($profile, $data)->data();
    }

    /**
     * @param array<string, mixed> $data
     */
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
        foreach ($definition->rules() as $rule) {
            $this->traverse($profile, $result, $rule, '', $anonymizedPaths, $missingPaths);
        }

        /** @var array<string, mixed> $result */
        return new AnonymizationResult($result, $anonymizedPaths, $missingPaths);
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function traverse(
        string $profile,
        array &$data,
        RuleDefinition $rule,
        string $parentPath,
        array &$anonymizedPaths,
        array &$missingPaths,
    ): void {
        $field = $rule->field();
        $path = $parentPath . '/' . $this->escapePathSegment($field);
        if (!array_key_exists($field, $data)) {
            $missingPaths[] = $path;
            return;
        }

        if ($rule->hasChildren()) {
            $this->traverseChildren($profile, $data[$field], $rule, $path, $anonymizedPaths, $missingPaths);
            return;
        }

        if ($rule->eachStrings()) {
            $this->traverseStringList($profile, $data[$field], $path, $anonymizedPaths);
            return;
        }

        $eachRules = $rule->eachRules();
        if ($eachRules !== null) {
            $this->traverseMapList($profile, $data[$field], $eachRules, $path, $anonymizedPaths, $missingPaths);
            return;
        }

        $this->anonymizeString($profile, $data[$field], $path, $anonymizedPaths);
    }

    /**
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function traverseChildren(
        string $profile,
        mixed &$value,
        RuleDefinition $rule,
        string $path,
        array &$anonymizedPaths,
        array &$missingPaths,
    ): void {
        if (!is_array($value) || (array_is_list($value) && $value !== [])) {
            throw UnsupportedValue::at($profile, $path, $value);
        }

        foreach ($rule->children() as $child) {
            $this->traverse($profile, $value, $child, $path, $anonymizedPaths, $missingPaths);
        }
    }

    /**
     * @param list<string> $anonymizedPaths
     */
    private function traverseStringList(string $profile, mixed &$value, string $path, array &$anonymizedPaths): void
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }

        foreach ($value as $index => $item) {
            $itemPath = $path . '/' . $index;
            $this->anonymizeString($profile, $item, $itemPath, $anonymizedPaths);
            $value[$index] = '[REDACTED]';
        }
    }

    /**
     * @param list<RuleDefinition> $rules
     * @param list<string> $anonymizedPaths
     * @param list<string> $missingPaths
     */
    private function traverseMapList(
        string $profile,
        mixed &$value,
        array $rules,
        string $path,
        array &$anonymizedPaths,
        array &$missingPaths,
    ): void {
        if (!is_array($value) || !array_is_list($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }

        foreach ($value as $index => &$item) {
            if (!is_array($item) || (array_is_list($item) && $item !== [])) {
                throw UnsupportedValue::at($profile, $path . '/' . $index, $item);
            }

            foreach ($rules as $child) {
                $this->traverse($profile, $item, $child, $path . '/' . $index, $anonymizedPaths, $missingPaths);
            }
        }
        unset($item);
    }

    /**
     * @param list<string> $anonymizedPaths
     */
    private function anonymizeString(string $profile, mixed &$value, string $path, array &$anonymizedPaths): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, $path, $value);
        }

        $value = '[REDACTED]';
        $anonymizedPaths[] = $path;
    }

    private function escapePathSegment(string $segment): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $segment);
    }
}
