<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Extension\Profile;

use InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Application\Extension\Profile\ProfileCollection;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileCompositionCompiler;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionData;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinitionParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileMetadataParser;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileRuleAppender;
use PhpAnonymizer\Anonymizer\Domain\Rule\Compiler\RuleOptionsCompiler;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfileCollection::class)]
#[UsesClass(ProfileDefinition::class)]
#[UsesClass(RuleOptionsCompiler::class)]
#[UsesClass(RuleDefinition::class)]
#[UsesClass(ProfileDefinitionParser::class)]
#[UsesClass(ProfileMetadataParser::class)]
#[UsesClass(ProfileCompositionCompiler::class)]
#[UsesClass(ProfileDefinitionData::class)]
#[UsesClass(ProfileRuleAppender::class)]
final class ProfileCollectionTest extends TestCase
{
    public function testItExposesProfilesByName(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);

        self::assertSame(['customer' => $profile], iterator_to_array(new ProfileCollection([$profile])->profiles()));
    }

    public function testItRejectsDuplicateNames(): void
    {
        $profile = ProfileDefinition::fromArray('customer', [
            'mode' => 'redact',
            'rules' => [['field' => 'email']],
        ]);

        $this->expectException(InvalidArgumentException::class);
        new ProfileCollection([$profile, $profile]);
    }
}
