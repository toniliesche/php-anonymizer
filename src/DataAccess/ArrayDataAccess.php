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
use RuntimeException;
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

    public function parseDataTree(mixed $data, array $path = []): Tree
    {
        if (!$this->supports($data)) {
            throw InvalidObjectTypeException::notAnArray(array_slice($path, 0, -1));
        }

        Assert::isArray($data);

        $tree = new Tree();

        foreach ($data as $key => $value) {
            $tree->addChildNode($this->parseDataNode($key, $value, [$key]));
        }

        return $tree;
    }

    /**
     * @param string[] $path
     */
    private function parseDataNode(string $key, mixed $data, array $path): Node
    {
        if (is_string($data)) {
            return new Node(
                name: $key,
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::LEAF,
                isList: false,
            );
        }

        $arrayType = ArrayTools::detectType($data);

        return match ($arrayType) {
            ArrayType::LIST => $this->parseListNode($key, $data, $path),
            ArrayType::MAP => $this->parseMapNode($key, $data, $path),
            ArrayType::MIXED => throw InvalidObjectTypeException::mixedArray($path),
        };
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $path
     */
    private function parseMapNode(string $key, array $data, array $path): Node
    {
        $childNodes = [];

        foreach ($data as $dataKey => $value) {
            $childNodes[] = $this->parseDataNode($dataKey, $value, $path);
        }

        return new Node(
            name: $key,
            dataAccess: DataAccess::ARRAY->value,
            nodeType: NodeType::NODE,
            isList: false,
            childNodes: $childNodes,
        );
    }

    /**
     * @param array<int, mixed> $data
     * @param string[] $path
     */
    private function parseListNode(string $key, array $data, array $path): Node
    {
        if (ArrayTools::isArrayOnlyArray($data)) {
            return $this->parseListOfArrays($key, $data, $path);
        }

        if (ArrayTools::isScalarOnlyArray($data)) {
            return new Node(
                name: $key,
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::LEAF,
                isList: true,
            );
        }

        throw new RuntimeException();
    }

    /**
     * @param array<int, array<int|string, mixed>> $data
     * @param string[] $path
     */
    private function parseListOfArrays(string $key, array $data, array $path): Node
    {
        $node = new Node(
            name: $key,
            dataAccess: DataAccess::ARRAY->value,
            nodeType: NodeType::NODE,
            isList: true,
        );

        $arrayType = null;
        foreach ($data as $listItem) {
            $currentArrayType = ArrayTools::detectType($listItem);
            $arrayType ??= $currentArrayType;

            if ($arrayType !== $currentArrayType) {
                throw new RuntimeException();
            }
        }

        foreach ($data as $listItem) {
            $node->mergeChildrenFromNode($this->parseDataNode($key, $listItem, $path), $path);
        }

        return $node;
    }
}
