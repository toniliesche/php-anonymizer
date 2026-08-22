<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Anonymization;

use InvalidArgumentException;
use Override;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizationLimits;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFacade;
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerFactory;
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
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileCollection;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileProviderInterface;
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
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\Payload\EmbeddedPayloadDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[CoversClass(AnonymizerFactory::class)]
#[CoversClass(AnonymizationLimits::class)]
#[CoversClass(ExecutionBudget::class)]
#[UsesClass(ArrayAnonymizer::class)]
#[UsesClass(JsonPointer::class)]
#[UsesClass(PreserveStringSelection::class)]
#[UsesClass(PreserveTraversalPolicy::class)]
#[UsesClass(RedactTraversalPolicy::class)]
#[UsesClass(AnonymizationReport::class)]
#[UsesClass(AnonymizationResult::class)]
#[UsesClass(ArrayTraversal::class)]
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
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(EmbeddedPayloadDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(ProfileCollection::class)]
#[UsesClass(CoreTransformationFactory::class)]
#[UsesClass(DateGeneralizeTransformation::class)]
#[UsesClass(NullifyTransformation::class)]
#[UsesClass(NumericBucketTransformation::class)]
#[UsesClass(PartialMaskTransformation::class)]
#[UsesClass(TransformationRegistry::class)]
final class AnonymizerFactoryTest extends TestCase
{
    public function testItCreatesAnAnonymizerFromNamedArrays(): void
    {
        $anonymizer = AnonymizerFactory::fromArrays([
            'default' => [
                'mode' => 'redact',
                'rules' => [['field' => 'email']],
            ],
        ]);

        self::assertInstanceOf(AnonymizerFacade::class, $anonymizer);
        self::assertSame(['email' => '[REDACTED]'], $anonymizer->anonymize('default', ['email' => 'secret']));
    }

    public function testItCreatesAnAnonymizerFromDefinitions(): void
    {
        $definition = ProfileDefinition::fromArray('default', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);

        self::assertSame(['email' => '[REDACTED]'], AnonymizerFactory::fromProfiles([$definition])->anonymize('default', ['email' => 'secret']));
    }

    public function testItFlattensProvidersInProviderAndYieldOrder(): void
    {
        $provider = (static fn (string $first, string $second): ProfileProviderInterface => new readonly class ($first, $second) implements ProfileProviderInterface {
            public function __construct(private string $first, private string $second)
            {
            }

            #[Override]
            public function profiles(): iterable
            {
                yield ProfileDefinition::fromArray($this->first, ['mode' => 'redact', 'rules' => [['field' => 'email']]]);

                yield ProfileDefinition::fromArray($this->second, ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
            }
        });

        $anonymizer = AnonymizerFactory::fromProfileProviders([
            $provider('first', 'second'),
            $provider('third', 'fourth'),
        ]);

        self::assertSame(['email' => '[REDACTED]'], $anonymizer->anonymize('third', ['email' => 'secret']));
        self::assertSame(['email' => '[REDACTED]'], $anonymizer->anonymize('fourth', ['email' => 'secret']));
    }

    public function testItSupportsGeneratorBackedProviders(): void
    {
        $provider = new class () implements ProfileProviderInterface {
            #[Override]
            public function profiles(): iterable
            {
                yield ProfileDefinition::fromArray('generated', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
            }
        };

        self::assertSame(['email' => '[REDACTED]'], AnonymizerFactory::fromProfileProviders([$provider])->anonymize('generated', ['email' => 'secret']));
    }

    public function testItRejectsDuplicateNamesAcrossProviders(): void
    {
        $profile = ProfileDefinition::fromArray('duplicate', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
        $provider = new readonly class ($profile) implements ProfileProviderInterface {
            public function __construct(private ProfileDefinition $profile)
            {
            }

            #[Override]
            public function profiles(): iterable
            {
                yield $this->profile;
            }
        };

        $this->expectException(InvalidArgumentException::class);
        AnonymizerFactory::fromProfileProviders([$provider, $provider]);
    }

    public function testItRetainsEmbeddedProfileReferenceValidation(): void
    {
        $profile = ProfileDefinition::fromArray('outer', ['mode' => 'redact', 'rules' => [['field' => 'payload', 'embedded' => ['codec' => 'json', 'profile' => 'missing']]]]);
        $provider = new readonly class ($profile) implements ProfileProviderInterface {
            public function __construct(private ProfileDefinition $profile)
            {
            }

            #[Override]
            public function profiles(): iterable
            {
                yield $this->profile;
            }
        };

        $this->expectException(InvalidArgumentException::class);
        AnonymizerFactory::fromProfileProviders([$provider]);
    }

    public function testItPropagatesProviderEnumerationExceptions(): void
    {
        $provider = new class () implements ProfileProviderInterface {
            #[Override]
            public function profiles(): iterable
            {
                throw new RuntimeException('provider failed');
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/provider failed/');
        AnonymizerFactory::fromProfileProviders([$provider]);
    }

    public function testItRejectsNonProviderValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AnonymizerFactory::fromProfileProviders([new stdClass()]);
    }
}
