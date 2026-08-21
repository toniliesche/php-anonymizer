<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Policy;

use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;

interface RuleTraversalPolicyInterface
{
    public function unselected(mixed &$value, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void;

    public function selected(string $profile, mixed &$value, RuleDefinition $rule, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void;

    public function scalar(string $profile, mixed &$value, ScalarAction $action, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void;

    /**
     * @param array<mixed, mixed> $value
     */
    public function strings(string $profile, array &$value, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void;
}
