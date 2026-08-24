<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Domain\Profile;

/**
 * @internal fragment/rule collision policy
 */
final class ProfileRuleAppender
{
    /**
     * @param list<array<string, mixed>> $result
     * @param array<string, array{index: int, direct: bool}> $fields
     * @param array<string, mixed> $rule
     */
    public function append(array &$result, array &$fields, array $rule, bool $direct): void
    {
        $field = $rule['field'] ?? null;
        if (!is_string($field) || $field === '') {
            $result[] = $rule;

            return;
        }
        if (!array_key_exists($field, $fields)) {
            $fields[$field] = ['index' => count($result), 'direct' => $direct];
            $result[] = $rule;

            return;
        }
        $existing = $fields[$field];
        if ($direct && !$existing['direct']) {
            $result[$existing['index']] = $rule;
            $fields[$field]['direct'] = true;

            return;
        }
        if ($direct) {
            $result[] = $rule;
        }
    }
}
