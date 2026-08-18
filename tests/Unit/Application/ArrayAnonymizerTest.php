<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application;

use Override;
use PhpAnonymizer\Anonymizer\Application\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
use PhpAnonymizer\Anonymizer\Application\PreserveArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\RedactArrayTraversal;
use PhpAnonymizer\Anonymizer\Application\RedactUnselectedValues;
use PhpAnonymizer\Anonymizer\Domain\ConditionDefinition;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleDefinition;
use PhpAnonymizer\Anonymizer\Infrastructure\InMemoryProfileProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayAnonymizer::class)]
#[CoversClass(AnonymizationResult::class)]
#[CoversClass(ProfileNotFound::class)]
#[CoversClass(UnsupportedValue::class)]
#[CoversClass(PreserveArrayTraversal::class)]
#[CoversClass(RedactArrayTraversal::class)]
#[CoversClass(RedactUnselectedValues::class)]
#[UsesClass(ConditionDefinition::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(InMemoryProfileProvider::class)]
final class ArrayAnonymizerTest extends TestCase
{
    private ArrayAnonymizer $anonymizer;

    #[Override]
    protected function setUp(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email'], ['field' => 'token']],
        ]);
        $this->anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));
    }

    public function testRedactsSelectedStringsAndPreservesOtherValues(): void
    {
        $data = ['email' => 'a@example.com', 'token' => '', 'name' => 'Ada', 'age' => 42];

        self::assertSame(
            ['email' => '[REDACTED]', 'token' => '[REDACTED]', 'name' => 'Ada', 'age' => 42],
            $this->anonymizer->anonymize('customer', $data),
        );
    }

    public function testLeavesMissingFieldsUntouched(): void
    {
        self::assertSame(['name' => 'Ada'], $this->anonymizer->anonymize('customer', ['name' => 'Ada']));
    }

    public function testDoesNotMutateInput(): void
    {
        $data = ['email' => 'a@example.com'];
        $this->anonymizer->anonymize('customer', $data);

        // @phpstan-ignore staticMethod.alreadyNarrowedType (documents input immutability)
        self::assertSame('a@example.com', $data['email']);
    }

    public function testReportsUnknownProfile(): void
    {
        $this->expectException(ProfileNotFound::class);
        $this->anonymizer->anonymize('missing', []);
    }

    public function testRejectsSelectedNonStringValues(): void
    {
        $this->expectException(UnsupportedValue::class);
        $this->expectExceptionMessageMatches('/customer/');
        $this->anonymizer->anonymize('customer', ['email' => ['nested' => true]]);
    }

    public function testTraversesNestedMapsAndListsAndReportsChangesAndMissingPaths(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'customer', 'children' => [['field' => 'email']]],
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $result = $anonymizer->anonymizeWithReport('nested', [
            'customer' => ['email' => 'a@example.com'],
            'orders' => [['reference' => 'one'], []],
            'tags' => ['internal', 'public'],
        ]);

        self::assertSame([
            'customer' => ['email' => '[REDACTED]'],
            'orders' => [['reference' => '[REDACTED]'], []],
            'tags' => ['[REDACTED]', '[REDACTED]'],
        ], $result->data());
        self::assertSame(['/customer/email', '/orders/0/reference', '/tags/0', '/tags/1'], $result->anonymizedPaths());
        self::assertSame(['/orders/1/reference'], $result->missingPaths());
    }

    public function testReportsMissingContainerOnlyOnce(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']]]],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('nested', []);

        self::assertSame(['/customer'], $result->missingPaths());
    }

    public function testRejectsWrongNestedShapeWithJsonPointer(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']]]],
        ]);

        $this->expectException(UnsupportedValue::class);
        $this->expectExceptionMessageMatches('#/customer#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('nested', ['customer' => ['unexpected']]);
    }

    public function testEscapesJsonPointerSegments(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'a/b~c']],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('nested', ['a/b~c' => 'secret']);

        self::assertSame(['/a~1b~0c'], $result->anonymizedPaths());
    }

    public function testAcceptsEmptyNestedMapsAndLists(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'customer', 'children' => [['field' => 'email']]],
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);

        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('nested', ['customer' => [], 'orders' => [], 'tags' => []]);

        self::assertSame(['/customer/email'], $result->missingPaths());
    }

    public function testRejectsWrongListAndScalarElementShapes(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $this->expectExceptionMessageMatches('#/orders#');
        $anonymizer->anonymize('nested', ['orders' => ['not-a-map'], 'tags' => []]);
    }

    public function testRejectsNonStringScalarListElement(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'tags', 'each' => true]],
        ]);

        $this->expectExceptionMessageMatches('#/tags/0#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('nested', ['tags' => [42]]);
    }

    public function testRejectsNonListForEach(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'tags', 'each' => true]],
        ]);

        $this->expectExceptionMessageMatches('#/tags#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('nested', ['tags' => ['name' => 'value']]);
    }

    public function testRejectsNonListForEachMapRules(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'orders', 'each' => [['field' => 'reference']]]],
        ]);

        $this->expectExceptionMessageMatches('#/orders#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('nested', ['orders' => ['first' => ['reference' => 'A-1']]]);
    }

    public function testPreserveModeKeepsSelectedValuesAndRedactsAllOtherStrings(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [
                ['field' => 'email'],
                ['field' => 'customer', 'children' => [['field' => 'name']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', [
                'email' => 'keep@example.com',
                'name' => 'hide',
                'customer' => ['name' => 'Keep', 'city' => 'Hide'],
                'tags' => ['one', 'two'],
                'nested' => ['secret' => 'hide'],
                'count' => 3,
            ]);

        self::assertSame([
            'email' => 'keep@example.com',
            'name' => '[REDACTED]',
            'customer' => ['name' => 'Keep', 'city' => '[REDACTED]'],
            'tags' => ['one', 'two'],
            'nested' => ['secret' => '[REDACTED]'],
            'count' => 3,
        ], $result->data());
        self::assertSame(['/name', '/customer/city', '/nested/secret'], $result->anonymizedPaths());
    }

    public function testConditionsUseOriginalSiblingValuesAndSkipNonMatches(): void
    {
        $profile = ProfileDefinition::fromArray('conditional', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'kind'],
                ['field' => 'email', 'when' => ['path' => 'kind', 'equals' => 'person']],
                ['field' => 'phone', 'when' => ['path' => 'kind', 'equals' => 'company']],
            ],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('conditional', [
                'kind' => 'person',
                'email' => 'a@example.com',
                'phone' => '0123',
            ]);

        self::assertSame('[REDACTED]', $result->data()['email']);
        self::assertSame('0123', $result->data()['phone']);
        self::assertSame(['/kind', '/email'], $result->anonymizedPaths());
        self::assertSame([], $result->missingPaths());
    }

    public function testScalarActionOverridesProfileMode(): void
    {
        $redact = ProfileDefinition::fromArray('redact-fallback', [
            'mode' => 'redact',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'preserve']],
        ]);
        $preserve = ProfileDefinition::fromArray('preserve-fallback', [
            'mode' => 'preserve',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'redact']],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$redact, $preserve]));

        self::assertSame(['address' => 'private'], $anonymizer->anonymize('redact-fallback', ['address' => 'private']));
        self::assertSame(['address' => '[REDACTED]'], $anonymizer->anonymize('preserve-fallback', ['address' => 'private']));
    }

    public function testPreserveModeReportsMissingSelectedFields(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'email'], ['field' => 'phone', 'when' => ['path' => 'kind', 'equals' => 'person']]],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['kind' => 'person']);

        self::assertSame(['/email', '/phone'], $result->missingPaths());
        self::assertSame(['/kind'], $result->anonymizedPaths());
    }

    public function testPreserveModeSkipsMissingConditionalRulesWhenPredicateDoesNotMatch(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'phone', 'when' => ['path' => 'kind', 'equals' => 'person']]],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['kind' => 'company']);

        self::assertSame(['/kind'], $result->anonymizedPaths());
        self::assertSame([], $result->missingPaths());
    }

    public function testPreserveModeTraversesListsOfSelectedMaps(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'orders', 'each' => [['field' => 'reference']]]],
        ]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['orders' => [
                ['reference' => 'keep', 'customer' => 'hide'],
            ]]);

        self::assertSame(['orders' => [['reference' => 'keep', 'customer' => '[REDACTED]']]], $result->data());
    }

    public function testPreserveModeRejectsSelectedInvalidValues(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [
                ['field' => 'email'],
                ['field' => 'address', 'children' => [['field' => 'street']]],
                ['field' => 'tags', 'each' => true],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        $this->expectException(UnsupportedValue::class);
        $anonymizer->anonymize('allow', ['email' => 42, 'address' => [], 'tags' => []]);
    }

    public function testPreserveModeRejectsSelectedLeafArray(): void
    {
        $profile = ProfileDefinition::fromArray('allow', ['mode' => 'preserve', 'rules' => [['field' => 'email']]]);

        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('allow', ['email' => []]);
    }

    public function testPreserveModeRejectsInvalidSelectedLists(): void
    {
        $strings = ProfileDefinition::fromArray('strings', ['mode' => 'preserve', 'rules' => [['field' => 'tags', 'each' => true]]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$strings]));
        $this->expectException(UnsupportedValue::class);
        $anonymizer->anonymize('strings', ['tags' => [42]]);
    }

    public function testPreserveModeRejectsInvalidSelectedMapLists(): void
    {
        $maps = ProfileDefinition::fromArray('maps', ['mode' => 'preserve', 'rules' => [['field' => 'orders', 'each' => [['field' => 'id']]]]]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$maps]));
        $this->expectException(UnsupportedValue::class);
        $anonymizer->anonymize('maps', ['orders' => ['id' => 'not-a-list']]);
    }

    public function testRedactScalarFallbackRejectsNonStringValues(): void
    {
        $profile = ProfileDefinition::fromArray('fallback', [
            'mode' => 'redact',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'preserve']],
        ]);

        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('fallback', ['address' => 42]);
    }

    public function testPreserveModeRejectsNonMapListElement(): void
    {
        $profile = ProfileDefinition::fromArray('maps', ['mode' => 'preserve', 'rules' => [['field' => 'orders', 'each' => [['field' => 'id']]]]]);

        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))->anonymize('maps', ['orders' => [42]]);
    }

    public function testRedactScalarActionCanRedactMixedShapeString(): void
    {
        $profile = ProfileDefinition::fromArray('fallback', [
            'mode' => 'redact',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']], 'on_scalar' => 'redact']],
        ]);

        self::assertSame(['address' => '[REDACTED]'], new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('fallback', ['address' => 'private']));
    }

    public function testPreserveModeRejectsContainerAndListShapeMismatches(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [
                ['field' => 'address', 'children' => [['field' => 'street']]],
                ['field' => 'tags', 'each' => true],
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
            ],
        ]);
        $anonymizer = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]));

        try {
            $anonymizer->anonymize('allow', ['address' => ['street'], 'tags' => [], 'orders' => []]);
            self::fail('Expected a selected map shape exception.');
        } catch (UnsupportedValue) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(UnsupportedValue::class);
        $anonymizer->anonymize('allow', ['address' => [], 'tags' => ['name' => 'value'], 'orders' => []]);
    }

    public function testPreserveModeRejectsScalarWithoutExplicitFallback(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'address', 'children' => [['field' => 'street']]]],
        ]);

        $this->expectException(UnsupportedValue::class);
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('allow', ['address' => 'private']);
    }

    public function testPreserveModeRejectsScalarWithoutFallbackForEachRules(): void
    {
        $profile = ProfileDefinition::fromArray('allow', [
            'mode' => 'preserve',
            'rules' => [['field' => 'orders', 'each' => [['field' => 'id']]]],
        ]);

        $this->expectExceptionMessageMatches('#/orders#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('allow', ['orders' => 'private']);
    }

    public function testPreserveModeRejectsScalarWithoutFallbackForStringLists(): void
    {
        $profile = ProfileDefinition::fromArray('allow', ['mode' => 'preserve', 'rules' => [['field' => 'tags', 'each' => true]]]);

        $this->expectExceptionMessageMatches('#/tags#');
        new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymize('allow', ['tags' => 'private']);
    }

    public function testPreserveModeReportsPreciseListErrorPaths(): void
    {
        $strings = ProfileDefinition::fromArray('strings', ['mode' => 'preserve', 'rules' => [['field' => 'tags', 'each' => true]]]);
        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$strings]))->anonymize('strings', ['tags' => [42]]);
            self::fail('Expected a list element error.');
        } catch (UnsupportedValue $exception) {
            self::assertStringContainsString('/tags/0', $exception->getMessage());
        }

        $maps = ProfileDefinition::fromArray('maps', ['mode' => 'preserve', 'rules' => [['field' => 'orders', 'each' => [['field' => 'id']]]]]);
        try {
            new ArrayAnonymizer(new InMemoryProfileProvider([$maps]))->anonymize('maps', ['orders' => [42]]);
            self::fail('Expected a map element error.');
        } catch (UnsupportedValue $exception) {
            self::assertStringContainsString('/orders/0', $exception->getMessage());
        }
    }

    public function testPreserveModeEscapesUnselectedPaths(): void
    {
        $profile = ProfileDefinition::fromArray('allow', ['mode' => 'preserve', 'rules' => [['field' => 'email']]]);
        $result = new ArrayAnonymizer(new InMemoryProfileProvider([$profile]))
            ->anonymizeWithReport('allow', ['email' => 'keep', 'a/b~c' => 'hide']);

        self::assertSame(['/a~1b~0c'], $result->anonymizedPaths());
    }
}
