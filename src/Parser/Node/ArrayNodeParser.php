<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Parser\Node;

use PhpAnonymizer\Anonymizer\Exception\InvalidNodeDefinitionException;
use PhpAnonymizer\Anonymizer\Exception\InvalidNodeNameException;
use PhpAnonymizer\Anonymizer\Model\NodeParsingResult;
use Safe\Exceptions\PcreException;
use function array_key_exists;
use function is_bool;
use function is_string;
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

        if (!is_string($node['name']) || \Safe\preg_match('/^[0-9a-zA-Z.\-_]+$/', $node['name']) === 0) {
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
