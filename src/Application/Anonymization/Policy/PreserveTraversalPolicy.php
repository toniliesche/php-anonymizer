<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Policy;

use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

final readonly class PreserveTraversalPolicy implements RuleTraversalPolicyInterface
{
    private RedactUnselectedValues $unselectedValues;
    private PreserveStringSelection $stringSelection;

    public function __construct(ReplacementStrategyRegistry $strategies)
    {
        $this->unselectedValues = new RedactUnselectedValues();
        $this->stringSelection = new PreserveStringSelection($strategies);
    }

    #[Override]
    public function unselected(mixed &$value, JsonPointer $path, AnonymizationReport $report): void
    {
        $paths = [];
        $this->unselectedValues->apply($value, (string) $path, $paths);
        foreach ($paths as $item) {
            $report->anonymized($this->pointer($item));
        }
    }

    #[Override]
    public function selected(string $profile, mixed &$value, RuleDefinition $rule, JsonPointer $path, AnonymizationReport $report): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        $paths = [];
        $this->stringSelection->apply($value, $rule, (string) $path, $profile, $paths);
        foreach ($paths as $item) {
            $report->anonymized($this->pointer($item));
        }
    }

    #[Override]
    public function scalar(string $profile, mixed &$value, ScalarAction $action, JsonPointer $path, AnonymizationReport $report): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        if ($action === ScalarAction::REDACT) {
            $this->unselected($value, $path, $report);
        }
    }

    #[Override]
    public function strings(string $profile, array &$value, JsonPointer $path, AnonymizationReport $report): void
    {
        if (!array_is_list($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        foreach ($value as $index => $item) {
            if (!is_string($item)) {
                throw UnsupportedValue::at($profile, (string) $path->append($index), $item);
            }
        }
    }

    private function pointer(string $path): JsonPointer
    {
        return JsonPointer::fromString($path);
    }
}
