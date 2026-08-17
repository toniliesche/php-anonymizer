<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application;

use Override;
use PhpAnonymizer\Anonymizer\Application\AnonymizationResult;
use PhpAnonymizer\Anonymizer\Application\ArrayAnonymizer;
use PhpAnonymizer\Anonymizer\Application\Exception\ProfileNotFound;
use PhpAnonymizer\Anonymizer\Application\Exception\UnsupportedValue;
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
}
