<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

use PhpAnonymizer\Anonymizer\Domain\Exception\InvalidProfileDefinition;
use PhpAnonymizer\Anonymizer\Domain\Rule\RuleDefinition;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class ProfileGlobalRuleParser
{
    /**
     * @param list<mixed> $definitions
     *
     * @return list<RuleDefinition>
     */
    public function parse(string $name, array $definitions): array
    {
        $rules = [];
        $fields = [];
        foreach ($definitions as $index => $definition) {
            if (!is_array($definition)) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s] must be a map', $index));
            }

            Assert::isMap($definition);

            try {
                $rule = RuleDefinition::fromGlobalArray($definition);
            } catch (InvalidProfileDefinition $exception) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s]: %s', $index, $exception->getMessage()));
            }
            if (isset($fields[$rule->field()])) {
                throw InvalidProfileDefinition::at($name, sprintf('global_rules[%s]: field "%s" is duplicated', $index, $rule->field()));
            }
            $fields[$rule->field()] = true;
            $rules[] = $rule;
        }

        return $rules;
    }
}
