<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFacade;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerRuntime;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\NdjsonAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\PayloadAnonymizationService;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\RuntimeAssembler;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\CompiledProfile;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\CompiledRuleList;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleSelector;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleTreeTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\SelectedRule;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\SelectedValueDispatcher;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\TraversalExecutionContext;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileInspection;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileInspector;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\EmbeddedProfileReferenceGraph;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\CoreTransformationFactory;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\DateGeneralizeTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\NullifyTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\NumericBucketTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\PartialMaskTransformation;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileGlobalRuleParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadata;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\EmbeddedPayloadDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleTraversalCombinationValidator;
use PhpAnonymizer\Anonymizer\Domain\Rule\TransformationDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

#[CoversClass(ProfileDefinition::class)]
#[CoversClass(ProfileMetadata::class)]
#[CoversClass(ProfileInspector::class)]
#[CoversClass(ProfileInspection::class)]
#[CoversClass(TransformationDefinition::class)]
#[CoversClass(ArrayAnonymizer::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactTraversalPolicy::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(ArrayTraversal::class)]
#[UsesClass(ExecutionBudget::class)]
#[UsesClass(RuleTreeTraversal::class)]
#[UsesClass(CompiledProfile::class)]
#[UsesClass(CompiledRuleList::class)]
#[UsesClass(RuleSelector::class)]
#[UsesClass(SelectedRule::class)]
#[UsesClass(EmbeddedPayloadTraversal::class)]
#[UsesClass(JsonPayloadCodec::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(ProfileReferenceValidator::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(CoreTransformationFactory::class)]
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(InvalidProfileDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
#[CoversClass(ProfileDefinitionParser::class)]
#[CoversClass(ProfileMetadataParser::class)]
#[CoversClass(ProfileCompositionCompiler::class)]
#[CoversClass(ProfileDefinitionData::class)]
#[CoversClass(ProfileRuleAppender::class)]
#[UsesClass(AnonymizerRuntime::class)]
#[UsesClass(AnonymizerFacade::class)]
#[UsesClass(NdjsonAnonymizationService::class)]
#[UsesClass(PayloadAnonymizationService::class)]
#[UsesClass(RuntimeAssembler::class)]
#[UsesClass(SelectedValueDispatcher::class)]
#[UsesClass(TraversalExecutionContext::class)]
#[UsesClass(DateGeneralizeTransformation::class)]
#[UsesClass(NullifyTransformation::class)]
#[UsesClass(NumericBucketTransformation::class)]
#[UsesClass(PartialMaskTransformation::class)]
#[UsesClass(ProfileGlobalRuleParser::class)]
#[UsesClass(EmbeddedPayloadDefinitionParser::class)]
#[UsesClass(RuleTraversalCombinationValidator::class)]
final class ProfileCompositionTest extends TestCase
{
    public function testMetadataAndFragmentNamesAreInspectable(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'metadata' => [
                'schema_version' => '1',
                'profile_version' => '2',
                'owner' => 'privacy',
                'status' => 'active',
                'purpose' => 'exports',
            ],
            'fragments' => [['name' => 'contact', 'rules' => [['field' => 'email']]]],
            'rules' => [['use' => 'contact']],
        ]);

        $inspection = new ProfileInspector(new InMemoryProfileProvider([$profile]))->inspect('customer');

        self::assertNotNull($inspection);
        $metadata = $inspection->metadata();
        self::assertNotNull($metadata);
        self::assertSame('customer', $inspection->name());
        self::assertSame('1', $metadata->schemaVersion());
        self::assertSame('2', $metadata->profileVersion());
        self::assertSame('privacy', $metadata->owner());
        self::assertSame('active', $metadata->status());
        self::assertSame('exports', $metadata->purpose());
        self::assertSame(['contact'], $inspection->fragmentNames());
        self::assertSame($profile->fingerprint(), $inspection->fingerprint());
    }

    public function testCompositionCompilerRejectsMalformedFragmentAndRuleCollectionsPrecisely(): void
    {
        $compiler = new ProfileCompositionCompiler();
        foreach ([
            ['rules' => ['named' => ['field' => 'email']]],
            ['fragments' => ['contact' => ['name' => 'contact', 'rules' => [['field' => 'email']]]]],
            ['fragments' => [['name' => 'contact']]],
            ['fragments' => [['rules' => [['field' => 'email']]]]],
            ['fragments' => [['name' => 'contact', 'rules' => ['email' => ['field' => 'email']]]], 'rules' => [['use' => 'contact']]],
            ['fragments' => [['name' => 'contact', 'rules' => [['field' => 'email']]]], 'rules' => [['use' => 42]]],
        ] as $definition) {
            try {
                $compiler->compile('customer', $definition);
                self::fail('Malformed fragment or rule collections should be rejected.');
            } catch (InvalidArgumentException|InvalidProfileDefinition $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testCompositionCompilerDistinguishesNestedPathsAndFragmentPrecedence(): void
    {
        $compiler = new ProfileCompositionCompiler();
        foreach ([
            ['field' => 'container', 'children' => ['invalid']],
            ['field' => 'items', 'each' => ['invalid']],
            ['field' => 'items', 'each' => [['field' => 'inner', 'children' => ['invalid']]]],
        ] as $rule) {
            try {
                $compiler->compile('customer', ['rules' => [$rule]]);
                self::fail('Malformed nested composition entries should be rejected.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertSame('nested rule 0 must be a map', $exception->getMessage());
            }
        }

        try {
            $compiler->compile('customer', [
                'fragments' => [['name' => 'base', 'rules' => [['field' => 'email']]]],
                'rules' => [['use' => 42]],
            ]);
            self::fail('A non-string fragment reference should be rejected.');
        } catch (InvalidProfileDefinition $exception) {
            self::assertSame('Profile "customer" is invalid at rules[0] must contain only a non-empty use name.', $exception->getMessage());
        }

        self::assertSame([
            'rules' => [['field' => 'email', 'strategy' => 'star_mask']],
            'fragments' => ['base'],
        ], $compiler->compile('customer', [
            'fragments' => [['name' => 'base', 'rules' => [['field' => 'email', 'strategy' => 'redact']]]],
            'rules' => [['use' => 'base'], ['field' => 'email', 'strategy' => 'star_mask']],
        ]));
    }

    public function testCompositionCompilerBoundsFragmentExpansionDepth(): void
    {
        $expand = new ReflectionMethod(ProfileCompositionCompiler::class, 'expandList');

        $this->expectException(InvalidProfileDefinition::class);
        $this->expectExceptionMessageIsOrContains('creates fragment cycle: one -> one');
        $expand->invoke(new ProfileCompositionCompiler(), 'customer', [], ['one' => []], ['one', 'one'], 'rules', true);
    }

    public function testMetadataDoesNotChangeBehaviorFingerprint(): void
    {
        $plain = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $governed = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'metadata' => ['profile_version' => '2', 'purpose' => 'exports'],
            'rules' => [['field' => 'email']],
        ]);

        self::assertSame($plain->fingerprint(), $governed->fingerprint());
        self::assertSame(['email' => '[REDACTED]'], new ArrayAnonymizer(new InMemoryProfileProvider([$governed]))->anonymize('customer', ['email' => 'alice@example.test']));
        self::assertNull($plain->metadata());
        self::assertSame([], $governed->globalRules());
    }

    public function testNestedFragmentsAndDirectRulesComposeDeterministically(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [
                ['name' => 'base', 'rules' => [['use' => 'contact']]],
                ['name' => 'contact', 'rules' => [['field' => 'email'], ['field' => 'phone']]],
            ],
            'rules' => [
                ['use' => 'base'],
                ['field' => 'email', 'strategy' => 'star_mask'],
                ['use' => 'base'],
            ],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('customer', [
            'email' => 'ab',
            'phone' => '123',
        ]);

        self::assertSame(['email' => '**', 'phone' => '[REDACTED]'], $result);
    }

    public function testFragmentsExpandInsideChildrenAndEachRules(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [[
                'name' => 'contact',
                'rules' => [['field' => 'email'], ['field' => 'phone']],
            ]],
            'rules' => [
                ['field' => 'profile', 'children' => [['use' => 'contact']]],
                ['field' => 'records', 'each' => [['use' => 'contact']]],
            ],
        ]);

        self::assertSame([
            'profile' => ['email' => '[REDACTED]', 'phone' => '[REDACTED]'],
            'records' => [[
                'email' => '[REDACTED]',
                'phone' => '[REDACTED]',
            ]],
        ], new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('customer', [
            'profile' => ['email' => 'alice@example.test', 'phone' => '123'],
            'records' => [['email' => 'bob@example.test', 'phone' => '456']],
        ]));
    }

    public function testFirstFragmentWinsAConflict(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [
                ['name' => 'first', 'rules' => [['field' => 'email', 'strategy' => 'star_mask']]],
                ['name' => 'second', 'rules' => [['field' => 'email']]],
            ],
            'rules' => [['use' => 'first'], ['use' => 'second']],
        ]);

        self::assertSame(['email' => '**'], new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('customer', ['email' => 'ab']));
    }

    public function testRejectsDuplicateNamesMissingReferencesAndCycles(): void
    {
        $this->expectExceptionMessageMatches('/duplicated/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [
                ['name' => 'contact', 'rules' => [['field' => 'email']]],
                ['name' => 'contact', 'rules' => [['field' => 'phone']]],
            ],
            'rules' => [['use' => 'contact']],
        ]);
    }

    public function testRejectsMissingFragmentReference(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        $this->expectExceptionMessageMatches('/missing fragment/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['use' => 'missing']]]);
    }

    public function testReportsNestedMissingFragmentPath(): void
    {
        $this->expectExceptionMessageIsOrContains('Profile "customer" is invalid at rules[0].children[0] references missing fragment "missing".');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'profile', 'children' => [['use' => 'missing']]]],
        ]);
    }

    public function testRejectsInvalidFragmentCollectionShapes(): void
    {
        $this->expectExceptionMessageMatches('/fragments must be a non-empty ordered list/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsFragmentNamesWithTrailingNewlineOrInvalidCharacter(): void
    {
        try {
            ProfileDefinition::fromArray('customer', [
                'mode' => 'redact',
                'fragments' => [['name' => "contact\n", 'rules' => [['field' => 'email']]]],
                'rules' => [['use' => "contact\n"]],
            ]);
            self::fail('A fragment name with a trailing newline should be rejected.');
        } catch (InvalidProfileDefinition $exception) {
            self::assertSame('Profile "customer" is invalid at fragments[0].name is invalid.', $exception->getMessage());
        }

        $this->expectExceptionMessageIsOrContains('Profile "customer" is invalid at fragments[0].name is invalid.');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [['name' => 'contact#', 'rules' => [['field' => 'email']]]],
            'rules' => [['use' => 'contact#']],
        ]);
    }

    public function testRejectsFragmentEntriesWithUnexpectedKeys(): void
    {
        $this->expectExceptionMessageMatches('/must contain exactly name and rules/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [['name' => 'contact', 'rules' => [['field' => 'email']], 'extra' => true]],
            'rules' => [['use' => 'contact']],
        ]);
    }

    public function testRejectsMalformedFragmentEntriesAndUseDeclarations(): void
    {
        try {
            ProfileDefinition::fromArray('customer', [
                'mode' => 'redact',
                'fragments' => [['name' => 'contact', 'rules' => []]],
                'rules' => [['use' => 'contact']],
            ]);
            self::fail('Empty fragment rules should be rejected.');
        } catch (InvalidProfileDefinition $exception) {
            self::assertStringContainsString('rules must be a non-empty list', $exception->getMessage());
        }

        $this->expectExceptionMessageMatches('/must contain only a non-empty use name/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [['name' => 'contact', 'rules' => [['field' => 'email']]]],
            'rules' => [['use' => 'contact', 'extra' => true]],
        ]);
    }

    public function testRejectsMalformedFragmentAndUseValueTypes(): void
    {
        $definitions = [
            ['fragments' => ['contact'], 'rules' => [['field' => 'email']]],
            ['fragments' => [['name' => 42, 'rules' => [['field' => 'email']]]], 'rules' => [['field' => 'email']]],
            ['fragments' => [['name' => 'contact', 'rules' => ['email']]], 'rules' => [['use' => 'contact']]],
            ['fragments' => [['name' => 'contact', 'rules' => [['field' => 'email']]]], 'rules' => [['use' => '']]],
            ['fragments' => [['name' => 'contact', 'rules' => [['field' => 'email']]]], 'rules' => [['use' => 42]]],
            ['fragments' => [['name' => 'contact', 'rules' => [['field' => 'email']]]], 'rules' => ['invalid']],
        ];

        foreach ($definitions as $index => $definition) {
            try {
                ProfileDefinition::fromArray('customer', ['mode' => 'redact', ...$definition]);
                self::fail(sprintf('Malformed fragment composition %s should be rejected.', $index));
            } catch (InvalidProfileDefinition $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testInspectorEnumeratesProfilesAndReturnsNullForUnknownNames(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $inspector = new ProfileInspector(new InMemoryProfileProvider([$profile]));

        self::assertNull($inspector->inspect('missing'));
        self::assertSame(['customer'], array_map(static fn (ProfileInspection $item): string => $item->name(), iterator_to_array($inspector->profiles())));
    }

    public function testRejectsInvalidMetadata(): void
    {
        $this->expectExceptionMessageMatches('/metadata.status/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'metadata' => ['status' => 'retired'],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsNonStringMetadataValueAtParserValueBoundary(): void
    {
        $this->expectExceptionMessageMatches('/metadata.schema_version must be a non-empty string/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'metadata' => ['schema_version' => 1],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsNonMapNestedRules(): void
    {
        $this->expectExceptionMessageMatches('/nested rule 0 must be a map/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'user', 'children' => [null]]],
        ]);
    }

    public function testTransformationDefinitionExposesItsContract(): void
    {
        $definition = new TransformationDefinition('date_shift', ['days' => 2], 'tenant-key');

        self::assertSame('date_shift', $definition->type());
        self::assertSame(['days' => 2], $definition->options());
        self::assertSame('tenant-key', $definition->keyId());
    }

    public function testRejectsEmptyMetadataMap(): void
    {
        $this->expectExceptionMessageMatches('/metadata must be a non-empty map/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'metadata' => [],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsUnknownMetadataKey(): void
    {
        $this->expectExceptionMessageMatches('/metadata contains an unknown key/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'metadata' => ['team' => 'privacy'],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsNonStringMetadataValue(): void
    {
        $this->expectExceptionMessageMatches('/metadata.owner must be a non-empty string/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'metadata' => ['owner' => 42],
            'rules' => [['field' => 'email']],
        ]);
    }

    public function testRejectsMalformedNestedRuleDefinitions(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        $this->expectExceptionMessageMatches('/nested rule 0 must be a map/');
        RuleDefinition::fromArray(['field' => 'email', 'children' => [null]]);
    }

    public function testRejectsObjectNestedRuleDefinitions(): void
    {
        $this->expectExceptionMessageMatches('/nested rule 0 must be a map/');
        RuleDefinition::fromArray(['field' => 'email', 'children' => [new stdClass()]]);
    }

    public function testRejectsFragmentCycles(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        $this->expectExceptionMessageMatches('/cycle/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [
                ['name' => 'one', 'rules' => [['use' => 'two']]],
                ['name' => 'two', 'rules' => [['use' => 'one']]],
            ],
            'rules' => [['use' => 'one']],
        ]);
    }

    public function testRejectsMalformedAndDuplicateFragmentsBeforeExpansion(): void
    {
        foreach ([
            [['name' => 'one', 'rules' => []]],
            [['name' => 'one', 'rules' => [['field' => 'email']]], ['name' => 'one', 'rules' => [['field' => 'name']]]],
        ] as $fragments) {
            try {
                ProfileDefinition::fromArray('customer', [
                    'mode' => 'redact', 'fragments' => $fragments, 'rules' => [['use' => 'one']],
                ]);
                self::fail('Expected malformed fragment declaration.');
            } catch (InvalidProfileDefinition $exception) {
                self::assertStringContainsString('fragment', mb_strtolower($exception->getMessage(), 'UTF-8'));
            }
        }
    }

    public function testRejectsUseEntriesWithExtraKeysAndUnknownNestedUses(): void
    {
        try {
            ProfileDefinition::fromArray('customer', [
                'mode' => 'redact', 'fragments' => [['name' => 'one', 'rules' => [['field' => 'email']]]],
                'rules' => [['use' => 'one', 'extra' => true]],
            ]);
            self::fail('Expected invalid use declaration.');
        } catch (InvalidProfileDefinition $exception) {
            self::assertStringContainsString('use', mb_strtolower($exception->getMessage(), 'UTF-8'));
        }

        $this->expectExceptionMessageIsOrContains('references missing fragment "missing"');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact', 'rules' => [['field' => 'container', 'children' => [['use' => 'missing']]]],
        ]);
    }

    public function testReportsCompleteFragmentCyclePath(): void
    {
        $this->expectExceptionMessageIsOrContains('Profile "customer" is invalid at rules.use(one).use(two).use(three)[0] creates fragment cycle: one -> two -> three -> one -> one.');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [
                ['name' => 'one', 'rules' => [['use' => 'two']]],
                ['name' => 'two', 'rules' => [['use' => 'three']]],
                ['name' => 'three', 'rules' => [['use' => 'one']]],
            ],
            'rules' => [['use' => 'one']],
        ]);
    }

    public function testDetectsFragmentCyclesInsideNestedChildren(): void
    {
        $this->expectExceptionMessageIsOrContains('Profile "customer" is invalid at rules.use(one)[0].children[0] creates fragment cycle: one -> one -> one.');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [[
                'name' => 'one',
                'rules' => [['field' => 'container', 'children' => [['use' => 'one']]]],
            ]],
            'rules' => [['use' => 'one']],
        ]);
    }

    public function testDetectsFragmentCyclesInsideEachRulesWithExactPath(): void
    {
        $this->expectExceptionMessageIsOrContains('Profile "customer" is invalid at rules.use(one)[0].each[0] creates fragment cycle: one -> one -> one.');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'fragments' => [[
                'name' => 'one',
                'rules' => [['field' => 'items', 'each' => [['use' => 'one']]]],
            ]],
            'rules' => [['use' => 'one']],
        ]);
    }

    public function testMetadataRejectsEmptyValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProfileMetadata(owner: ' ');
    }
}
