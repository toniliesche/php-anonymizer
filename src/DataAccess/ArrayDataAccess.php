<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use PhpAnonymizer\Anonymizer\Enum\ArrayType;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Helpers\ArrayTools;
use PhpAnonymizer\Anonymizer\Model\Data\Node;
use PhpAnonymizer\Anonymizer\Model\Data\Tree;
use Webmozart\Assert\Assert;
use function array_key_exists;
use function array_slice;
use function is_array;

final class ArrayDataAccess implements DataAccessInterface
{
    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
        }

        /** @var array<string,mixed> $parent */
        return array_key_exists($name, $parent) && isset($parent[$name]);
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
        }

        /** @var array<string,mixed> $parent */
        return $parent[$name] ?? throw FieldDoesNotExistException::fromPath($path);
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        if (!$this->supports($parent)) {
            throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
        }

        /** @var array<string,mixed> $parent */
        if (!array_key_exists($name, $parent)) {
            throw FieldDoesNotExistException::fromPath($path);
        }

        $parent[$name] = $newValue;
    }

    public function supports(mixed $parent): bool
    {
        return is_array($parent);
    }

    public function parseDataTree(array $path, mixed $data): Tree
    {
        if (!$this->supports($data)) {
            throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
        }

        Assert::isArray($data);

        $nodes = [];

        return new Tree(
            childNodes: $nodes,
        );
    }

    private function parseDataNode(string $key, mixed $data): Node
    {
        if (!is_array($data)) {
            return new Node(
                name: $key,
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::LEAF,
                isList: false,
            );
        }

        $arrayType = ArrayTools::detect($data);

        return match ($arrayType) {
            ArrayType::LIST => $this->parseListNode($key, $data),
            ArrayType::MAP => $this->parseMapNode($key, $data),
            ArrayType::MIXED => throw InvalidObjectTypeException::mixedArray(),
        };
    }

    private function parseMapNode(string $key, array $data): Node
    {
        $childNodes = [];

        foreach ($data as $dataKey => $value) {
            $childNodes[] = $this->parseDataNode($dataKey, $value);
        }

        return new Node(
            name: $key,
            dataAccess: DataAccess::ARRAY->value,
            nodeType: NodeType::NODE,
            isList: false,
            childNodes: $childNodes,
        );
    }

    private function parseListNode(string $key, array $data): Node
    {
        $arrayType = null;
        foreach ($data as $listItem) {
            $currentArrayType = ArrayTools::detect($listItem);
        }

        return new Node(
            name: $key,
            dataAccess: DataAccess::ARRAY->value,
            nodeType: $nodeType,
            isList: true,
            childNodes: $childNodes,
        );
    }
}
