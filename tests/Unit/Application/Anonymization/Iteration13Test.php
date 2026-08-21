<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use LogicException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationContext;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Path\JsonPointer;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveStringSelection;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\PreserveTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Policy\RedactTraversalPolicy;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationReport;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\AuditEntry;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\DocumentPayloadAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Result\NdjsonAuditResult;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\ExecutionBudget;
use PhpAnonymizer\Anonymizer\Application\Anonymization\Traversal\RuleTreeTraversal;
use PhpAnonymizer\Anonymizer\Application\Exception\ExecutionLimitExceeded;
use PhpAnonymizer\Anonymizer\Application\Exception\PayloadCodecFailure;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\DocumentPayloadCodecInterface;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\EmbeddedPayloadTraversal;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\PayloadCodecRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\EmbeddedProfileReferenceGraph;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\Validation\ProfileReferenceValidator;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\DefaultRedactionStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\ReplacementStrategyRegistry;
use PhpAnonymizer\Anonymizer\Application\Extension\Replacement\StarMaskStrategy;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\CoreTransformationFactory;
use PhpAnonymizer\Anonymizer\Application\Extension\Transformation\TransformationRegistry;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRoot;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\Profile\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(DocumentAnonymizationResult::class)]
#[CoversClass(DocumentPayloadAnonymizationResult::class)]
#[CoversClass(NdjsonAnonymizationResult::class)]
#[CoversClass(NdjsonAuditResult::class)]
#[CoversClass(JsonPayloadCodec::class)]
#[CoversClass(ProfileDefinition::class)]
#[CoversClass(ProfileRoot::class)]
#[UsesClass(AnonymizationContext::class)]
#[UsesClass(AnonymizationLimits::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactTraversalPolicy::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(AuditEntry::class)]
#[CoversClass(ArrayTraversal::class)]
#[UsesClass(ExecutionBudget::class)]
#[UsesClass(RuleTreeTraversal::class)]
#[UsesClass(EmbeddedPayloadTraversal::class)]
#[UsesClass(PayloadCodecRegistry::class)]
#[UsesClass(ProfileRegistry::class)]
#[UsesClass(EmbeddedProfileReferenceGraph::class)]
#[UsesClass(ProfileReferenceValidator::class)]
#[UsesClass(DefaultRedactionStrategy::class)]
#[UsesClass(ReplacementStrategyRegistry::class)]
#[UsesClass(StarMaskStrategy::class)]
#[UsesClass(CoreTransformationFactory::class)]
#[UsesClass(TransformationRegistry::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
#[UsesClass(ExecutionLimitExceeded::class)]
#[UsesClass(PayloadCodecFailure::class)]
#[UsesClass(ProfileNotFound::class)]
final class Iteration13Test extends TestCase
{
    public function testDocumentListRootIsExplicitAndReported(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $result = $anonymizer->anonymizeDocumentWithReport('records', 'json', '[{"email":"one"},{"email":"two"}]');

        self::assertSame('[{"email":"[REDACTED]"},{"email":"[REDACTED]"}]', $result->payload());
        self::assertSame(['/0/email', '/1/email'], $result->anonymizationResult()->anonymizedPaths());
    }

    public function testMapDocumentRetainsLegacyShape(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        self::assertSame('{"email":"[REDACTED]"}', $anonymizer->anonymizeDocument('customer', 'json', '{"email":"secret"}'));
        self::assertSame(ProfileRoot::MAP, $profile->root());
    }

    public function testNdjsonIsLazyAndAggregatesWildcardAudit(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
        $stream = $anonymizer->anonymizeNdjson('records', ['{"email":"one"}', '{"email":"two"}']);

        $this->expectException(LogicException::class);
        $stream->audit();
    }

    public function testNdjsonOutputAndAuditAfterExhaustion(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
        $stream = $anonymizer->anonymizeNdjson('records', ["{\"email\":\"one\"}\n", '{"email":"two"}']);

        self::assertSame(['{"email":"[REDACTED]"}', '{"email":"[REDACTED]"}'], iterator_to_array($stream->lines(), false));
        $audit = $stream->audit();

        self::assertSame('/*/email', $audit->entries()[0]->path());
        self::assertSame(2, $audit->entries()[0]->count());
    }

    public function testNdjsonRejectsNonObjectRecordsWhenPulled(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $stream = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymizeNdjson('records', ['[]']);

        $this->expectException(InvalidArgumentException::class);
        iterator_to_array($stream->lines(), false);
    }

    public function testNdjsonRequiresListProfile(): void
    {
        $profile = ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $this->expectException(InvalidArgumentException::class);
        $anonymizer->anonymizeNdjson('customer', []);
    }

    public function testContextualDocumentMethodAcceptsContext(): void
    {
        $profile = ProfileDefinition::fromArray('records', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
            'root' => 'list',
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        self::assertSame('[{"email":"[REDACTED]"}]', $anonymizer->anonymizeDocumentWithContext('records', 'json', '[{"email":"secret"}]', new AnonymizationContext(['tenant' => 'acme'])));
    }

    public function testContextualNdjsonMethodProcessesRecords(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $stream = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymizeNdjsonWithContext('records', ['{"email":"secret"}'], new AnonymizationContext(['tenant' => 'acme']));

        self::assertSame(['{"email":"[REDACTED]"}'], iterator_to_array($stream->lines(), false));
    }

    public function testDocumentRejectsRootShapesAndListItems(): void
    {
        $mapProfile = ProfileDefinition::fromArray('map', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $listProfile = ProfileDefinition::fromArray('list', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$mapProfile, $listProfile]));

        try {
            $anonymizer->anonymizeDocument('map', 'json', '[]');
            self::fail('A map profile must reject a JSON list document.');
        } catch (InvalidArgumentException) {
        }

        $shapeCodec = new class () implements DocumentPayloadCodecInterface {
            #[Override]
            public function id(): string
            {
                return 'shape';
            }

            #[Override]
            public function decode(string $payload): array
            {
                return ['email' => 'x'];
            }

            #[Override]
            public function encode(array $data): string
            {
                return '{}';
            }

            #[Override]
            public function decodeDocument(string $payload): array
            {
                return $payload === 'list' ? [['email' => 'x']] : ['email' => 'x'];
            }

            #[Override]
            public function encodeDocument(array $data): string
            {
                return '{}';
            }
        };
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$mapProfile, $listProfile]), codecs: [$shapeCodec]);

        try {
            $anonymizer->anonymizeDocument('map', 'shape', 'list');
            self::fail('A map profile must reject a list document.');
        } catch (InvalidArgumentException) {
        }

        try {
            $anonymizer->anonymizeDocument('list', 'shape', 'map');
            self::fail('A list profile must reject a map document.');
        } catch (InvalidArgumentException) {
        }
        $this->expectException(InvalidArgumentException::class);
        $anonymizer->anonymizeDocument('list', 'json', '["scalar"]');
    }

    public function testDocumentResultExposesAllReportCollections(): void
    {
        $result = new DocumentAnonymizationResult(['email' => 'x'], ['/email'], ['/name'], [['path' => '/email', 'action' => 'transform', 'handler' => 'mask']]);

        self::assertSame(['email' => 'x'], $result->data());
        self::assertSame(['/email'], $result->anonymizedPaths());
        self::assertSame(['/name'], $result->missingPaths());
        self::assertSame([['path' => '/email', 'action' => 'transform', 'handler' => 'mask']], $result->events());
    }

    public function testDocumentCodecFailuresAreSafe(): void
    {
        $profile = ProfileDefinition::fromArray('map', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new InMemoryProfileProvider([$profile]);
        $anonymizer = new ArrayAnonymizer($provider);
        $this->expectException(InvalidArgumentException::class);
        $anonymizer->anonymizeDocument('map', 'missing', '{}');
    }

    public function testDocumentCoversMissingProfileAndEncodingFailure(): void
    {
        $profile = ProfileDefinition::fromArray('map', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new InMemoryProfileProvider([$profile]);
        $anonymizer = new ArrayAnonymizer($provider);

        try {
            $anonymizer->anonymizeDocument('missing', 'json', '{}');
            self::fail('Unknown profile must fail.');
        } catch (RuntimeException) {
        }
        $failingCodec = new class () implements DocumentPayloadCodecInterface {
            #[Override]
            public function id(): string
            {
                return 'failing';
            }

            #[Override]
            public function decode(string $payload): array
            {
                return [];
            }

            #[Override]
            public function encode(array $data): string
            {
                return '';
            }

            #[Override]
            public function decodeDocument(string $payload): array
            {
                return ['email' => 'x'];
            }

            #[Override]
            public function encodeDocument(array $data): string
            {
                throw new RuntimeException('encode failed');
            }
        };
        $this->expectException(PayloadCodecFailure::class);
        new ArrayAnonymizer($provider, codecs: [$failingCodec])->anonymizeDocument('map', 'failing', '{}');
    }

    public function testDocumentRejectsMalformedAndOversizedInput(): void
    {
        $profile = ProfileDefinition::fromArray('map', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new InMemoryProfileProvider([$profile]);
        $anonymizer = new ArrayAnonymizer($provider);

        try {
            $anonymizer->anonymizeDocument('map', 'json', '{');
            self::fail('Malformed JSON must fail.');
        } catch (RuntimeException) {
        }
        $limited = new ArrayAnonymizer($provider, limits: new AnonymizationLimits(maxInputBytes: 1));
        $this->expectException(ExecutionLimitExceeded::class);
        $limited->anonymizeDocument('map', 'json', '{}');
    }

    public function testNdjsonRejectsBlankAndMalformedRecords(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            iterator_to_array($anonymizer->anonymizeNdjson('records', [])->lines(), false);
        } catch (Throwable) {
            self::fail('An empty stream is valid.');
        }
        foreach ([[''], ['{']] as $lines) {
            $this->expectException(Throwable::class);
            iterator_to_array($anonymizer->anonymizeNdjson('records', $lines)->lines(), false);
        }
    }

    public function testNdjsonRejectsMalformedJsonRecord(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $this->expectException(PayloadCodecFailure::class);
        iterator_to_array($anonymizer->anonymizeNdjson('records', ['{'])->lines(), false);
    }

    public function testNdjsonCoversNonStringAndMissingProfile(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array($anonymizer->anonymizeNdjson('records', [42])->lines(), false);
    }

    public function testJsonDocumentRejectsScalarRootAndUnknownNdjsonProfile(): void
    {
        $profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            $anonymizer->anonymizeDocument('records', 'json', 'null');
            self::fail('Scalar JSON documents must fail.');
        } catch (PayloadCodecFailure) {
        }
        $this->expectException(RuntimeException::class);
        $anonymizer->anonymizeNdjson('missing', []);
    }
}
