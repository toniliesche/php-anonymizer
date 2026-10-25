<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\CompiledProfile;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\CompiledRuleList;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleSelector;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\SelectedRule;
use PhpAnonymizer\Anonymizer\Domain\Profile\KeyNormalizer;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileGlobalRuleParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\EmbeddedPayloadDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Condition\ConditionDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompiledProfile::class)]
#[CoversClass(CompiledRuleList::class)]
#[CoversClass(RuleSelector::class)]
#[CoversClass(SelectedRule::class)]
#[UsesClass(ConditionDefinition::class)]
#[UsesClass(ConditionDefinitionParser::class)]
#[UsesClass(KeyNormalizer::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(ProfileDefinition::class)]
#[CoversClass(RuleDefinition::class)]
#[UsesClass(EmbeddedPayloadDefinitionParser::class)]
#[UsesClass(RuleTraversalCombinationValidator::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
#[UsesClass(ProfileGlobalRuleParser::class)]
final class CompiledRuleSelectionTest extends TestCase
{
    public function testCompilesExactNormalizedAndNestedLookups(): void
    {
        $nested = RuleDefinition::fromArray(['field' => 'address', 'children' => [['field' => 'email']]]);
        $each = RuleDefinition::fromArray(['field' => 'tags', 'each' => [['field' => 'value']]]);
        $first = RuleDefinition::fromArray(['field' => 'email_address']);
        $exact = RuleDefinition::fromArray(['field' => 'emailAddress']);
        $compiled = CompiledRuleList::compile([$nested, $each, $first, $exact], true);

        self::assertSame($exact, $compiled->resolve('emailAddress', true));
        self::assertSame($first, $compiled->resolve('email-address', true));
        self::assertNull($compiled->resolve('email-address', false));
        self::assertSame($nested->children()[0], $compiled->nested($nested)?->resolve('email', false));
        $eachRules = $each->eachRules();
        self::assertNotNull($eachRules);
        self::assertSame($eachRules[0], $compiled->nested($each)?->resolve('value', false));
        self::assertSame([], CompiledRuleList::empty()->rules());
    }

    public function testCompilesProfileScopedAndGlobalLists(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'global_rules' => [['field' => 'phone']],
        ]);
        $compiled = new CompiledProfile($profile);

        self::assertSame('email', $compiled->rules->rules()[0]->field());
        self::assertSame('phone', $compiled->globalRules->rules()[0]->field());
        self::assertSame([], $compiled->emptyRules->rules());
    }

    public function testSelectorFallsThroughToConditionalGlobalRule(): void
    {
        $local = RuleDefinition::fromArray(['field' => 'email', 'when' => ['path' => 'kind', 'equals' => 'person']]);
        $global = RuleDefinition::fromGlobalArray(['field' => 'email', 'when' => ['path' => 'kind', 'equals' => 'company']]);
        $selector = new RuleSelector(
            CompiledRuleList::compile([$local], false),
            CompiledRuleList::compile([$global], false),
            ['kind' => 'company'],
            false,
        );

        $selected = $selector->select('email');

        self::assertInstanceOf(SelectedRule::class, $selected);
        self::assertSame($global, $selected->rule);

        $localSelector = new RuleSelector(CompiledRuleList::compile([$local], false), CompiledRuleList::empty(), ['kind' => 'person'], false);
        self::assertSame($local, $localSelector->select('email')?->rule);
        self::assertNull($localSelector->select('unknown'));
    }

    public function testSelectorReportsOnlyMatchingScopedMissingRules(): void
    {
        $present = RuleDefinition::fromArray(['field' => 'present']);
        $missing = RuleDefinition::fromArray(['field' => 'missing']);
        $conditional = RuleDefinition::fromArray(['field' => 'skipped', 'when' => ['path' => 'kind', 'equals' => 'person']]);
        $selector = new RuleSelector(
            CompiledRuleList::compile([$present, $missing, $conditional], false),
            CompiledRuleList::empty(),
            ['present' => 'value', 'kind' => 'company'],
            false,
        );
        $report = AnonymizationReport::collecting();

        $selector->reportMissing(JsonPointer::root(), $report);

        self::assertSame(['/missing'], $report->missingPaths());
    }
}
