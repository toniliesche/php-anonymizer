<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ContextualReplacementStrategyInterface;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactUnselectedValues;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ReplacementContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\PayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleTreeTraversal;
use PhpAnonymizer\Anonymizer\Application\Exception\ContextRequired;
use PhpAnonymizer\Anonymizer\Application\Exception\ExecutionLimitExceeded;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\EmbeddedProfileReferenceGraph;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(AnonymizationLimits::class)]
#[CoversClass(ExecutionLimitExceeded::class)]
#[CoversClass(ExecutionBudget::class)]
#[CoversClass(AuditEntry::class)]
#[CoversClass(AuditAnonymizationResult::class)]
#[CoversClass(ContextRequired::class)]
#[CoversClass(AnonymizationContext::class)]
#[CoversClass(ReplacementContext::class)]
#[CoversClass(PreserveStringSelection::class)]
#[CoversClass(RedactTraversalPolicy::class)]
#[CoversClass(RuleOptionsCompiler::class)]
#[CoversClass(ProfileReferenceValidator::class)]
#[UsesClass(PayloadAnonymizationResult::class)]
#[UsesClass(InvalidProfileDefinition::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactUnselectedValues::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(ArrayTraversal::class)]
#[UsesClass(RuleTreeTraversal::class)]
#[UsesClass(EmbeddedPayloadTraversal::class)]
#[UsesClass(JsonPayloadCodec::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[CoversClass(ProfileDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
#[UsesClass(ProfileNotFound::class)]
final class Iteration10Test extends TestCase
{
    public function testNoReportPathReturnsSameTransformation(): void
    {
        $anonymizer = $this->engine();

        self::assertSame(
            $anonymizer->anonymizeWithReport('customer', ['email' => 'secret'])->data(),
            $anonymizer->anonymize('customer', ['email' => 'secret']),
        );
    }

    public function testContextIsPassedToAContextualStrategy(): void
    {
        $strategy = new class implements ContextualReplacementStrategyInterface {
            #[Override]
            public function id(): string
            {
                return 'contextual';
            }
            #[Override]
            public function replace(string $value): string
            {
                return $value;
            }
            #[Override]
            public function replaceWithContext(string $value, ReplacementContext $context): string
            {
                return $context->profile() . ':' . $context->path() . ':' . $context->keyId() . ':' . $context->attributes()['tenant'];
            }
        };
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'contextual', 'key_id' => 'primary']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy]);

        self::assertSame(['email' => 'customer:/email:primary:acme'], $anonymizer->anonymizeWithContext('customer', ['email' => 'secret'], new AnonymizationContext(['tenant' => 'acme'])));
    }

    public function testContextualStrategyRequiresContext(): void
    {
        $strategy = new class implements ContextualReplacementStrategyInterface {
            #[Override]
            public function id(): string
            {
                return 'contextual';
            }
            #[Override]
            public function replace(string $value): string
            {
                return $value;
            }
            #[Override]
            public function replaceWithContext(string $value, ReplacementContext $context): string
            {
                return $value;
            }
        };
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'contextual', 'key_id' => 'primary']]]);
        $this->expectException(ContextRequired::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy])->anonymize('customer', ['email' => 'secret']);
    }

    public function testContextualReportPayloadBatchAndAuditOperations(): void
    {
        $strategy = new class implements ContextualReplacementStrategyInterface {
            #[Override]
            public function id(): string
            {
                return 'contextual';
            }
            #[Override]
            public function replace(string $value): string
            {
                return 'safe';
            }
            #[Override]
            public function replaceWithContext(string $value, ReplacementContext $context): string
            {
                return 'safe-' . $context->keyId();
            }
        };
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'contextual', 'key_id' => 'primary']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy]);
        $context = new AnonymizationContext(['tenant' => 'acme']);

        self::assertSame(['email' => 'safe-primary'], $anonymizer->anonymizeWithReportAndContext('customer', ['email' => 'secret'], $context)->data());
        self::assertSame('{"email":"safe-primary"}', $anonymizer->anonymizePayloadWithContext('customer', 'json', '{"email":"secret"}', $context));
        self::assertCount(1, iterator_to_array($anonymizer->anonymizeRecordsWithContext('customer', [['email' => 'secret']], $context), false));
        self::assertSame(['contextual'], array_map(static fn(AuditEntry $entry): string => (string) $entry->handler(), $anonymizer->anonymizeWithAuditAndContext('customer', ['email' => 'secret'], $context)->entries()));
        try {
            iterator_to_array($anonymizer->anonymizeRecordsWithContext('customer', [['secret']], $context), false);
            self::fail('Expected invalid batch record.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(InvalidArgumentException::class, $exception::class);
        }
    }

    public function testContextualStrategyWorksInPreserveMode(): void
    {
        $strategy = new class implements ContextualReplacementStrategyInterface {
            #[Override]
            public function id(): string
            {
                return 'contextual';
            }
            #[Override]
            public function replace(string $value): string
            {
                return $value;
            }
            #[Override]
            public function replaceWithContext(string $value, ReplacementContext $context): string
            {
                return 'pseudonym';
            }
        };
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'preserve', 'rules' => [['field' => 'email', 'strategy' => 'contextual', 'key_id' => 'primary']]]);
        self::assertSame(['email' => 'pseudonym'], new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy])->anonymizeWithContext('customer', ['email' => 'secret'], new AnonymizationContext()));

        $this->expectException(ContextRequired::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy])->anonymize('customer', ['email' => 'secret']);
    }

    public function testContextAndKeyIdValidation(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [['field' => 'email', 'key_id' => 'primary']]]);
    }

    public function testContextRejectsInvalidAttributesAndBatchRejectsInvalidRecords(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AnonymizationContext([1 => 'invalid']);
    }

    public function testKeyIdMustBeNonEmptyString(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'redact', 'key_id' => 7]]]);
    }

    public function testKeyIdRequiresContextualStrategy(): void
    {
        $profile = ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'redact', 'key_id' => 'primary']]]);
        $this->expectException(InvalidArgumentException::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
    }

    public function testContextualStrategyRequiresKeyId(): void
    {
        $strategy = new class implements ContextualReplacementStrategyInterface {
            #[Override]
            public function id(): string
            {
                return 'contextual';
            }
            #[Override]
            public function replace(string $value): string
            {
                return $value;
            }
            #[Override]
            public function replaceWithContext(string $value, ReplacementContext $context): string
            {
                return $value;
            }
        };
        $profile = ProfileDefinition::fromArray('invalid', ['mode' => 'redact', 'rules' => [['field' => 'email', 'strategy' => 'contextual']]]);
        $this->expectException(InvalidArgumentException::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), [$strategy]);
    }

    public function testBatchIsLazyAndProcessesRecordsIndependently(): void
    {
        $anonymizer = $this->engine();
        $records = (static function (): iterable {
            yield ['email' => 'one'];
            yield ['email' => 'two'];
        })();

        self::assertSame([
            ['email' => '[REDACTED]'],
            ['email' => '[REDACTED]'],
        ], iterator_to_array($anonymizer->anonymizeRecords('customer', $records), false));
    }

    public function testBatchRejectsListRecords(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array($this->engine()->anonymizeRecords('customer', [['secret']]), false);
    }

    public function testLimitsIncludeCurrentPointer(): void
    {
        $anonymizer = new ArrayAnonymizer(
            new InMemoryProfileProvider([ProfileDefinition::fromArray('customer', [
                'mode' => 'redact', 'rules' => [['field' => 'email']],
            ])]),
            limits: new AnonymizationLimits(maxVisitedNodes: 1),
        );

        $this->expectException(ExecutionLimitExceeded::class);
        $this->expectExceptionMessageMatches('/email/');
        $anonymizer->anonymize('customer', ['email' => 'secret']);
    }

    public function testBudgetAcceptsValuesWithinAllLimits(): void
    {
        $budget = new ExecutionBudget(new AnonymizationLimits());
        $path = JsonPointer::root()->append('email');
        $budget->visit('customer', $path, 1);
        $budget->collection('customer', $path, 1);
        $budget->inputBytes('customer', (string) $path, 1);
        $budget->enterEmbedded('customer', $path);
        $budget->leaveEmbedded();
        self::assertSame('/email', (string) $path);
    }

    public function testBudgetRejectsDepthCollectionAndInputLimits(): void
    {
        $path = JsonPointer::root()->append('email');
        $budget = new ExecutionBudget(new AnonymizationLimits(maxInputBytes: 1, maxCollectionEntries: 1));
        try {
            $budget->collection('customer', $path, 2);
            self::fail('Expected collection limit.');
        } catch (ExecutionLimitExceeded) {
        }
        try {
            $budget->inputBytes('customer', (string) $path, 2);
            self::fail('Expected input limit.');
        } catch (ExecutionLimitExceeded) {
        }
        $this->expectException(ExecutionLimitExceeded::class);
        new ExecutionBudget(new AnonymizationLimits(maxDepth: 1))->visit('customer', $path, 2);
    }

    public function testBudgetRejectsEmbeddedDepthAndInputLimits(): void
    {
        $path = JsonPointer::root()->append('payload');
        $budget = new ExecutionBudget(new AnonymizationLimits(maxInputBytes: 1, maxEmbeddedPayloadDepth: 1));
        $budget->enterEmbedded('customer', $path);
        try {
            $budget->inputBytes('customer', (string) $path, 2);
            self::fail('Expected embedded input limit.');
        } catch (ExecutionLimitExceeded) {
        }
        $this->expectException(ExecutionLimitExceeded::class);
        $budget->enterEmbedded('customer', $path);
    }

    public function testBudgetAcceptsExactConfiguredBoundariesAndRestoresEmbeddedDepth(): void
    {
        $path = JsonPointer::root()->append('payload');
        $budget = new ExecutionBudget(new AnonymizationLimits(
            maxInputBytes: 2,
            maxDepth: 2,
            maxVisitedNodes: 2,
            maxCollectionEntries: 2,
            maxEmbeddedPayloadDepth: 2,
        ));

        $budget->visit('customer', $path, 2);
        $budget->visit('customer', $path, 2);
        $budget->collection('customer', $path, 2);
        $budget->inputBytes('customer', (string) $path, 2);
        $budget->enterEmbedded('customer', $path);
        $budget->enterEmbedded('customer', $path);
        $budget->leaveEmbedded();
        $budget->enterEmbedded('customer', $path);
        self::assertSame('/payload', (string) $path);
    }

    public function testAuditAggregatesWithoutValues(): void
    {
        $result = $this->engine()->anonymizeWithAudit('customer', ['email' => 'secret']);

        self::assertSame('[REDACTED]', $result->data()['email']);
        self::assertCount(1, $result->entries());
        self::assertSame('customer', $result->entries()[0]->profile());
        self::assertNotSame('', $result->entries()[0]->fingerprint());
        self::assertSame('/email', $result->entries()[0]->path());
        self::assertSame('replace', $result->entries()[0]->action());
        self::assertSame('redact', $result->entries()[0]->handler());
        self::assertSame(1, $result->entries()[0]->count());
        self::assertStringNotContainsString('secret', serialize($result->entries()));
    }

    public function testLimitsRejectNonPositiveValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AnonymizationLimits(maxDepth: 0);
    }

    public function testAuditRejectsUnknownProfile(): void
    {
        $this->expectException(ProfileNotFound::class);
        $this->engine()->anonymizeWithAudit('missing', []);
    }

    public function testPayloadInputLimitIsReportedDirectly(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]), limits: new AnonymizationLimits(maxInputBytes: 1));

        $this->expectException(ExecutionLimitExceeded::class);
        $anonymizer->anonymizePayload('customer', 'json', '{"email":"secret"}');
    }

    public function testPreserveScalarRedactionIsAuditedWithRedactHandler(): void
    {
        $profile = ProfileDefinition::fromArray('preserve', [
            'mode' => 'preserve',
            'rules' => [['field' => 'email', 'children' => [['field' => 'value']], 'on_scalar' => 'redact']],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymizeWithAudit('preserve', ['email' => 'secret']);

        self::assertSame('redact', $result->entries()[0]->handler());
    }

    public function testDisabledReportDropsAllKindsOfEvents(): void
    {
        $report = AnonymizationReport::disabled();
        $path = JsonPointer::root()->append('secret');
        $report->anonymized($path, 'replace', 'redact');
        $report->missing($path);
        $report->event($path, 'custom', 'handler');

        self::assertFalse($report->isCollecting());
        self::assertSame([], $report->anonymizedPaths());
        self::assertSame([], $report->missingPaths());
        self::assertSame([], $report->events());
    }

    public function testReportMergePrefixesAllPathsAndPreservesEventMetadata(): void
    {
        $child = AnonymizationReport::collecting();
        $child->anonymized(JsonPointer::root()->append('email'), 'replace', 'redact');
        $child->missing(JsonPointer::root()->append('name'));
        $child->event(JsonPointer::root()->append('custom'), 'observe');
        $result = new AnonymizationResult(['email' => '[REDACTED]'], $child->anonymizedPaths(), $child->missingPaths(), $child->events());

        $parent = AnonymizationReport::collecting();
        $parent->merge($result, JsonPointer::root()->append('user'));

        self::assertSame(['/user/email'], $parent->anonymizedPaths());
        self::assertSame(['/user/name'], $parent->missingPaths());
        self::assertSame([
            ['path' => '/user/email', 'action' => 'replace', 'handler' => 'redact'],
            ['path' => '/user/name', 'action' => 'missing', 'handler' => null],
            ['path' => '/user/custom', 'action' => 'observe', 'handler' => null],
        ], $parent->events());
    }

    private function engine(): ArrayAnonymizer
    {
        return new ArrayAnonymizer(new InMemoryProfileProvider([
            ProfileDefinition::fromArray('customer', [
                'mode' => 'redact', 'rules' => [['field' => 'email']],
            ]),
        ]));
    }
}
