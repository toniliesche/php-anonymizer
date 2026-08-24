<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Policy;

use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ContextualReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ReplacementContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ValueTransformationContext;
use PhpAnonymizer\Anonymizer\Application\Exception\ContextRequired;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ContextualValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\CoreTransformationFactory;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;

// @internal
final readonly class RedactTraversalPolicy implements RuleTraversalPolicyInterface
{
    private TransformationRegistry $transformations;

    public function __construct(private ReplacementStrategyRegistry $strategies, ?TransformationRegistry $transformations = null)
    {
        $this->transformations = $transformations ?? new TransformationRegistry(CoreTransformationFactory::defaults());
    }

    #[Override]
    public function unselected(mixed &$value, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void
    {
    }

    #[Override]
    public function selected(string $profile, mixed &$value, RuleDefinition $rule, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void
    {
        if ($rule->transformation() instanceof TransformationDefinition) {
            $this->transform($profile, $value, $rule->transformation(), $path, $report, $context);

            return;
        }
        $this->replace($profile, $value, $path, $rule->strategy() ?? 'redact', $rule->keyId(), $report, $context);
    }

    #[Override]
    public function scalar(string $profile, mixed &$value, ScalarAction $action, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        if ($action === ScalarAction::REDACT) {
            $this->replace($profile, $value, $path, 'redact', null, $report, $context);
        }
    }

    #[Override]
    public function strings(string $profile, array &$value, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void
    {
        if (!array_is_list($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        foreach ($value as $index => &$item) {
            $this->replace($profile, $item, $path->append($index), 'redact', null, $report, $context);
        }
        unset($item);
    }

    private function transform(string $profile, mixed &$value, TransformationDefinition $definition, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context): void
    {
        $transformation = $this->transformations->get($definition->type());
        if (!$transformation instanceof ValueTransformationInterface) {
            throw new UnsupportedValue(sprintf('Value transformation "%s" is not available.', $definition->type()));
        }
        $transformation->validateOptions($definition->options());
        if ($transformation instanceof ContextualValueTransformationInterface) {
            if (!$context instanceof AnonymizationContext) {
                throw ContextRequired::forStrategy($profile, (string) $path, $definition->type());
            } $value = $transformation->transformWithContext($value, $definition->options(), new ValueTransformationContext($profile, $path, $definition->keyId(), $context));
        } else {
            $value = $transformation->transform($value, $definition->options());
        }
        $report->anonymized($path, 'transform', $definition->type());
    }

    private function replace(string $profile, mixed &$value, JsonPointer $path, string $strategyId, ?string $keyId, AnonymizationReport $report, ?AnonymizationContext $context): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        $strategy = $this->strategies->get($strategyId);
        if (!$strategy instanceof ReplacementStrategyInterface) {
            throw new UnsupportedValue(sprintf('Replacement strategy "%s" is not available.', $strategyId));
        }
        if ($strategy instanceof ContextualReplacementStrategyInterface) {
            if (!$context instanceof AnonymizationContext || $keyId === null) {
                throw ContextRequired::forStrategy($profile, (string) $path, $strategyId);
            }
            $value = $strategy->replaceWithContext($value, new ReplacementContext($profile, $path, $keyId, $context));
        } else {
            $value = $strategy->replace($value);
        }
        $report->anonymized($path, 'replace', $strategyId);
    }
}
