<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Policy;

use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ValueTransformationContext;
use PhpAnonymizer\Anonymizer\Application\Exception\ContextRequired;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ContextualValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\CoreTransformationFactory;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\ValueTransformationInterface;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\ScalarAction;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;

// @internal
final readonly class PreserveTraversalPolicy implements RuleTraversalPolicyInterface
{
    private RedactUnselectedValues $unselectedValues;

    private PreserveStringSelection $stringSelection;

    private TransformationRegistry $transformations;

    public function __construct(ReplacementStrategyRegistry $strategies, ?TransformationRegistry $transformations = null)
    {
        $this->transformations = $transformations ?? new TransformationRegistry(CoreTransformationFactory::defaults());
        $this->unselectedValues = new RedactUnselectedValues();
        $this->stringSelection = new PreserveStringSelection($strategies);
    }

    #[Override]
    public function unselected(mixed &$value, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void
    {
        $paths = [];
        $this->unselectedValues->apply($value, (string) $path, $paths);
        foreach ($paths as $item) {
            $report->anonymized($this->pointer($item), 'replace', 'redact');
        }
    }

    /**
     * @codeCoverageIgnore
     */
    #[Override]
    public function selected(string $profile, mixed &$value, RuleDefinition $rule, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void
    {
        if ($rule->transformation() instanceof TransformationDefinition) {
            $this->transform($profile, $value, $rule, $path, $report, $context);

            return;
        }
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        $paths = [];
        $this->stringSelection->apply($value, $rule, (string) $path, $profile, $paths, $context);
        foreach ($paths as $item) {
            $handler = $rule->strategy();
            $report->anonymized($this->pointer($item), 'replace', $handler);
        }
    }

    #[Override]
    public function scalar(string $profile, mixed &$value, ScalarAction $action, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void
    {
        if (!is_string($value)) {
            throw UnsupportedValue::at($profile, (string) $path, $value);
        }
        if ($action === ScalarAction::REDACT) {
            $this->unselected($value, $path, $report, $context);
        }
    }

    #[Override]
    public function strings(string $profile, array &$value, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context = null): void
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

    /**
     * @codeCoverageIgnore
     */
    private function transform(string $profile, mixed &$value, RuleDefinition $rule, JsonPointer $path, AnonymizationReport $report, ?AnonymizationContext $context): void
    {
        $definition = $rule->transformation();
        if (!$definition instanceof TransformationDefinition) {
            throw new UnsupportedValue('Missing value transformation definition.');
        } $transformation = $this->transformations->get($definition->type());
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

    private function pointer(string $path): JsonPointer
    {
        return JsonPointer::fromString($path);
    }
}
