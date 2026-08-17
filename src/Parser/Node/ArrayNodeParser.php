<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

use PhpAnonymizer\Anonymizer\Exception\InvalidNodeDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Model\Rule\NodeParsingResult;
use Safe\Exceptions\PcreException;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;
use function Safe\preg_match;
use function sprintf;

final class ArrayNodeParser implements NodeParserInterface
{
    private const STRING_SETTINGS = [
        'data_access',
        'value_type',
        'nested_type',
        'nested_rule',
        'filter_field',
        'filter_value',
    ];

    /**
     * @throws PcreException
     */
    public function parseNode(array|string $node, string $path): NodeParsingResult
    {
        if (is_string($node)) {
            return new NodeParsingResult(isValid: false);
        }

        $this->validateName($node, $path);
        $this->validateOptions($node, $path);
        $this->validateConstraints($node, $path);

        return new NodeParsingResult(
            isValid: true,
            isArray: $node['is_array'] ?? false,
            property: $node['name'],
            dataAccess: $node['data_access'] ?? null,
            valueType: $node['value_type'] ?? null,
            nestedType: $node['nested_type'] ?? null,
            nestedRule: $node['nested_rule'] ?? null,
            filterField: $node['filter_field'] ?? null,
            filterValue: $node['filter_value'] ?? null,
            fallbackMatch: $node['fallback']['match'] ?? null,
            fallbackAnonymize: $node['fallback']['anonymize'] ?? false,
            fallbackValueType: $node['fallback']['value_type'] ?? null,
        );
    }

    /**
     * @param array<mixed> $node
     */
    private function validateName(array $node, string $path): void
    {
        if (!array_key_exists('name', $node)) {
            throw new InvalidNodeDefinitionException(
                sprintf(
                    'Mandatory setting "name" not found for node [path: %s].',
                    $path,
                ),
            );
        }

        if (!is_string($node['name']) || preg_match('/^[0-9a-zA-Z.\-_]+$/', $node['name']) === 0) {
            throw new InvalidNodeNameException(
                sprintf(
                    'Invalid node name "%s" [path: %s].',
                    $node['name'],
                    $path,
                ),
            );
        }
    }

    /**
     * @param array<mixed> $node
     *
     * @throws PcreException
     */
    private function validateOptions(array $node, string $path): void
    {
        foreach (self::STRING_SETTINGS as $setting) {
            if (array_key_exists($setting, $node) && (!is_string($node[$setting]) || trim($node[$setting]) === '')) {
                throw new InvalidNodeDefinitionException(
                    sprintf(
                        'Setting "%s" for node "%s" must be of type string and non-empty [path: %s, type: %s].',
                        $setting,
                        $node['name'],
                        $path,
                        get_debug_type($node[$setting]),
                    ),
                );
            }
        }

        if (array_key_exists('is_array', $node) && !is_bool($node['is_array'])) {
            throw new InvalidNodeDefinitionException(
                sprintf(
                    'Setting "is_array" for node "%s" must be of type bool [path: %s, type: %s].',
                    $node['name'],
                    $path,
                    get_debug_type($node['is_array']),
                ),
            );
        }

        if (!array_key_exists('fallback', $node)) {
            return;
        }

        if (!is_array($node['fallback'])) {
            throw new InvalidNodeDefinitionException(
                sprintf(
                    'Setting "fallback" for node "%s" must be an array [path: %s, type: %s].',
                    $node['name'],
                    $path,
                    get_debug_type($node['fallback']),
                ),
            );
        }

        $fallback = $node['fallback'];
        if (($fallback['match'] ?? null) !== 'scalar') {
            throw new InvalidNodeDefinitionException(
                sprintf('Fallback match for node "%s" must be "scalar" [path: %s].', $node['name'], $path),
            );
        }

        if (array_key_exists('anonymize', $fallback) && !is_bool($fallback['anonymize'])) {
            throw new InvalidNodeDefinitionException(
                sprintf(
                    'Fallback setting "anonymize" for node "%s" must be of type bool [path: %s, type: %s].',
                    $node['name'],
                    $path,
                    get_debug_type($fallback['anonymize']),
                ),
            );
        }

        if (array_key_exists('value_type', $fallback) && (!is_string($fallback['value_type']) || trim($fallback['value_type']) === '')) {
            throw new InvalidNodeDefinitionException(
                sprintf(
                    'Fallback setting "value_type" for node "%s" must be of type string and non-empty [path: %s, type: %s].',
                    $node['name'],
                    $path,
                    get_debug_type($fallback['value_type']),
                ),
            );
        }

        if (($fallback['anonymize'] ?? false) === false && array_key_exists('value_type', $fallback)) {
            throw new InvalidNodeDefinitionException(
                sprintf('Fallback value_type for node "%s" requires anonymize to be true [path: %s].', $node['name'], $path),
            );
        }

        if (!array_key_exists('children', $node)) {
            throw new InvalidNodeDefinitionException(
                sprintf('Fallback can only be defined on a node with children [path: %s].', $path),
            );
        }
    }

    /**
     * @param array<mixed> $node
     */
    private function validateConstraints(array $node, string $path): void
    {
        if (!array_key_exists('value_type', $node)) {
            return;
        }

        if (array_key_exists('children', $node) || array_key_exists('nested_type', $node) || array_key_exists('nested_rule', $node)) {
            throw new InvalidNodeDefinitionException(
                sprintf(
                    'Node "%s" cannot have value_type and children at the same time [path: %s].',
                    $node['name'],
                    $path,
                ),
            );
        }
    }
}
