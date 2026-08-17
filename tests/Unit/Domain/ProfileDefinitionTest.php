<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Domain;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\RuleDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileDefinition::class)]
#[CoversClass(RuleDefinition::class)]
#[CoversClass(InvalidProfileDefinition::class)]
final class ProfileDefinitionTest extends TestCase
{
    public function testCreatesAFlatRedactionProfile(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);

        self::assertSame('customer', $profile->name());
        self::assertSame('email', $profile->rules()[0]->field());
    }

    public function testRejectsInvalidName(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('not valid', ['mode' => 'redact', 'rules' => [['field' => 'email']]]);
    }

    public function testRejectsUnexpectedTopLevelKeys(): void
    {
        $this->expectExceptionMessageMatches('/keys must be exactly mode and rules/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [], 'extra' => true]);
    }

    public function testRejectsUnsupportedMode(): void
    {
        $this->expectExceptionMessageMatches('/mode must be redact/');
        ProfileDefinition::fromArray('customer', ['mode' => 'preserve', 'rules' => [['field' => 'email']]]);
    }

    public function testRejectsEmptyOrAssociativeRules(): void
    {
        $this->expectException(InvalidProfileDefinition::class);
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => ['email' => ['field' => 'email']]]);
    }

    public function testRejectsMalformedRule(): void
    {
        $this->expectExceptionMessageMatches('/rules\[0\]/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => '']]]);
    }

    public function testRejectsNonArrayRule(): void
    {
        $this->expectExceptionMessageMatches('/must be a map/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => ['email']]);
    }

    public function testRejectsNonStringField(): void
    {
        $this->expectExceptionMessageMatches('/field must be a non-empty string/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 42]]]);
    }

    public function testRejectsUnknownRuleKeys(): void
    {
        $this->expectExceptionMessageMatches('/rule keys must contain exactly field/');
        ProfileDefinition::fromArray('customer', ['mode' => 'redact', 'rules' => [['field' => 'email', 'extra' => true]]]);
    }

    public function testRejectsDuplicateFields(): void
    {
        $this->expectExceptionMessageMatches('/duplicated/');
        ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email'], ['field' => 'email']],
        ]);
    }

    public function testCreatesNestedMapAndListRules(): void
    {
        $profile = ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [
                ['field' => 'customer', 'children' => [['field' => 'email']]],
                ['field' => 'tags', 'each' => true],
                ['field' => 'orders', 'each' => [['field' => 'reference']]],
            ],
        ]);

        self::assertTrue($profile->rules()[0]->hasChildren());
        self::assertCount(1, $profile->rules()[0]->children());
        self::assertTrue($profile->rules()[1]->eachStrings());
        self::assertNotNull($profile->rules()[2]->eachRules());
    }

    public function testRejectsBothTraversalForms(): void
    {
        $this->expectExceptionMessageMatches('/both children and each/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email']], 'each' => true]],
        ]);
    }

    public function testRejectsDuplicateNestedSiblingFields(): void
    {
        $this->expectExceptionMessageMatches('/duplicates field "email"/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => [['field' => 'email'], ['field' => 'email']]]],
        ]);
    }

    public function testRejectsMalformedEachDefinition(): void
    {
        $this->expectExceptionMessageMatches('/each must be true or a non-empty list/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'tags', 'each' => []]],
        ]);
    }

    public function testRejectsMalformedChildrenDefinition(): void
    {
        $this->expectExceptionMessageMatches('/children must be a non-empty list/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => []]],
        ]);
    }

    public function testRejectsNonMapNestedRule(): void
    {
        $this->expectExceptionMessageMatches('/nested rule 0 must be a map/');
        ProfileDefinition::fromArray('nested', [
            'mode' => 'redact',
            'rules' => [['field' => 'customer', 'children' => ['email']]],
        ]);
    }
}
