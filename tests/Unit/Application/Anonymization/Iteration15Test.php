<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleTreeTraversal;
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
use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadata;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileDefinition::class)]
#[CoversClass(ProfileMetadata::class)]
#[CoversClass(ProfileInspector::class)]
#[CoversClass(ProfileInspection::class)]
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
#[UsesClass(InvalidProfileDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
final class Iteration15Test extends TestCase
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
            ],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('customer', [
            'email' => 'ab',
            'phone' => '123',
        ]);

        self::assertSame(['email' => '**', 'phone' => '[REDACTED]'], $result);
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

    public function testMetadataRejectsEmptyValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProfileMetadata(owner: ' ');
    }
}
