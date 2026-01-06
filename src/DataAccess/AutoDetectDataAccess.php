<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\DataAccess;

use PhpAnonymizer\Anonymizer\Enum\ArrayType;
use PhpAnonymizer\Anonymizer\Enum\DataAccess;
use PhpAnonymizer\Anonymizer\Enum\NodeType;
use PhpAnonymizer\Anonymizer\Exception\FieldDoesNotExistException;
use PhpAnonymizer\Anonymizer\Exception\InvalidArgumentException;
use PhpAnonymizer\Anonymizer\Exception\InvalidObjectTypeException;
use PhpAnonymizer\Anonymizer\Helpers\ArrayTools;
use PhpAnonymizer\Anonymizer\Model\Data\Node;
use PhpAnonymizer\Anonymizer\Model\Data\Tree;
use ReflectionClass;
use RuntimeException;
use function array_keys;
use function array_slice;
use function get_class_methods;
use function get_object_vars;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function lcfirst;
use function sprintf;
use function str_starts_with;
use function substr;
use function ucfirst;

final readonly class AutoDetectDataAccess implements DataAccessInterface
{
    /**
     * @param DataAccessInterface[] $dataAccesses
     */
    public function __construct(
        private array $dataAccesses,
    ) {
        foreach ($this->dataAccesses as $dataAccess) {
            if (!$dataAccess instanceof DataAccessInterface) {
                throw new InvalidArgumentException('All data accesses must implement DataAccessInterface');
            }
        }
    }

    public function hasChild(array $path, mixed $parent, string $name): bool
    {
        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($parent) && $dataAccess->hasChild($path, $parent, $name)) {
                return true;
            }
        }

        return false;
    }

    public function getChild(array $path, mixed $parent, string $name): mixed
    {
        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($parent) && $dataAccess->hasChild($path, $parent, $name)) {
                try {
                    return $dataAccess->getChild($path, $parent, $name);
                    // @codeCoverageIgnoreStart
                } catch (FieldDoesNotExistException) {
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
    }

    public function setChildValue(array $path, mixed &$parent, string $name, mixed $newValue): void
    {
        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($parent) && $dataAccess->hasChild($path, $parent, $name)) {
                try {
                    $dataAccess->setChildValue($path, $parent, $name, $newValue);

                    return;
                    // @codeCoverageIgnoreStart
                } catch (FieldDoesNotExistException) {
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        throw FieldDoesNotExistException::orIsNotAccessibleFromPath($path);
    }

    public function supports(mixed $parent): bool
    {
        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($parent)) {
                return true;
            }
        }

        return false;
    }

    public function parseDataTree(mixed $data, array $path = []): Tree
    {
        if (is_array($data)) {
            return $this->parseArrayTree($data, $path);
        }

        if (is_object($data)) {
            return $this->parseObjectTree($data, $path);
        }

        throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
    }

    /**
     * @param array<int|string, mixed> $data
     * @param string[] $path
     */
    private function parseArrayTree(array $data, array $path): Tree
    {
        $tree = new Tree();

        foreach ($data as $key => $value) {
            $node = $this->parseArrayNode((string) $key, $value, array_merge($path, [(string) $key]));
            if ($node !== null) {
                $tree->addChildNode($node);
            }
        }

        return $tree;
    }

    /**
     * @param string[] $path
     */
    private function parseArrayNode(string $key, mixed $data, array $path): ?Node
    {
        if (is_string($data)) {
            return new Node(
                name: $key,
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::LEAF,
                isList: false,
            );
        }

        if (is_array($data)) {
            $arrayType = ArrayTools::detectType($data);

            return match ($arrayType) {
                ArrayType::LIST => $this->parseArrayListNode($key, $data, $path),
                ArrayType::MAP => $this->parseArrayMapNode($key, $data, $path),
                ArrayType::MIXED => throw InvalidObjectTypeException::mixedArray($path),
            };
        }

        if (is_object($data)) {
            return $this->parseArrayObjectNode($key, $data, $path);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $path
     */
    private function parseArrayMapNode(string $key, array $data, array $path): Node
    {
        $childNodes = [];

        foreach ($data as $dataKey => $value) {
            $childNode = $this->parseArrayNode($dataKey, $value, $path);
            if ($childNode !== null) {
                $childNodes[] = $childNode;
            }
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
    private function parseArrayListNode(string $key, array $data, array $path): Node
    {
        if (ArrayTools::isArrayOnlyArray($data)) {
            return $this->parseArrayListOfArrays($key, $data, $path);
        }

        if (ArrayTools::isObjectOnlyArray($data)) {
            return $this->parseArrayListOfObjects($key, $data, $path);
        }

        if (ArrayTools::isScalarOnlyArray($data)) {
            return new Node(
                name: $key,
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::LEAF,
                isList: true,
            );
        }

        throw InvalidObjectTypeException::mixedArray($path);
    }

    /**
     * @param array<int, array<int|string, mixed>> $data
     * @param string[] $path
     */
    private function parseArrayListOfArrays(string $key, array $data, array $path): Node
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
                throw new RuntimeException(sprintf('Inconsistent array types in list at path: %s', implode('.', $path)));
            }
        }

        foreach ($data as $listItem) {
            $childNode = $this->parseArrayNode($key, $listItem, $path);
            if ($childNode !== null) {
                $node->mergeChildrenFromNode($childNode, $path);
            }
        }

        return $node;
    }

    /**
     * @param array<int, object> $data
     * @param string[] $path
     */
    private function parseArrayListOfObjects(string $key, array $data, array $path): Node
    {
        $node = new Node(
            name: $key,
            dataAccess: DataAccess::ARRAY->value,
            nodeType: NodeType::NODE,
            isList: true,
        );

        foreach ($data as $listItem) {
            $tree = $this->parseObjectTree($listItem, $path);
            $tempNode = new Node(
                name: $key,
                dataAccess: DataAccess::ARRAY->value,
                nodeType: NodeType::NODE,
                isList: false,
                childNodes: $tree->childNodes,
            );

            $node->mergeChildrenFromNode($tempNode, $path);
        }

        return $node;
    }

    /**
     * @param string[] $path
     */
    private function parseArrayObjectNode(string $key, object $data, array $path): Node
    {
        $tree = $this->parseObjectTree($data, $path);

        return new Node(
            name: $key,
            dataAccess: DataAccess::ARRAY->value,
            nodeType: NodeType::NODE,
            isList: false,
            childNodes: $tree->childNodes,
        );
    }

    /**
     * @param string[] $path
     */
    private function parseObjectTree(object $data, array $path): Tree
    {
        $tree = new Tree();
        $hasSupportedAccess = false;

        foreach ($this->dataAccesses as $dataAccess) {
            if ($dataAccess->supports($data)) {
                $hasSupportedAccess = true;

                break;
            }
        }

        if (!$hasSupportedAccess) {
            throw InvalidObjectTypeException::notAnObject(array_slice($path, 0, -1));
        }

        foreach ($this->collectObjectFieldNames($data) as $name) {
            $mergedNode = null;

            foreach ($this->dataAccesses as $dataAccess) {
                if (!$dataAccess->supports($data)) {
                    continue;
                }

                $childPath = $path;
                $childPath[] = $name;

                try {
                    if (!$dataAccess->hasChild($childPath, $data, $name)) {
                        continue;
                    }
                } catch (InvalidObjectTypeException) {
                    continue;
                }

                try {
                    $value = $dataAccess->getChild($childPath, $data, $name);
                } catch (FieldDoesNotExistException|InvalidObjectTypeException) {
                    continue;
                }

                $childNode = $this->parseObjectChildNode(
                    $name,
                    $value,
                    $this->resolveDataAccessName($dataAccess),
                    $childPath,
                );
                if ($childNode === null) {
                    continue;
                }

                if ($mergedNode === null) {
                    $mergedNode = $childNode;

                    continue;
                }

                $this->mergeChildNode($mergedNode, $childNode, $childPath);
            }

            if ($mergedNode !== null) {
                $tree->addChildNode($mergedNode);
            }
        }

        return $tree;
    }

    /**
     * @return string[]
     */
    private function collectObjectFieldNames(object $data): array
    {
        $names = [];

        foreach (array_keys(get_object_vars($data)) as $name) {
            $names[$name] = true;
        }

        $reflection = new ReflectionClass($data);
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isInitialized($data)) {
                continue;
            }

            if ($property->getValue($data) === null) {
                continue;
            }

            if ($property->isReadOnly()) {
                continue;
            }

            $names[$property->getName()] = true;
        }

        $methods = get_class_methods($data);
        foreach ($methods as $methodName) {
            if (!str_starts_with($methodName, 'get')) {
                continue;
            }

            $nodeName = lcfirst(substr($methodName, 3));
            if ($nodeName === '') {
                continue;
            }

            $setter = 'set' . ucfirst($nodeName);
            if (!in_array($setter, $methods, true)) {
                continue;
            }

            $names[$nodeName] = true;
        }

        return array_keys($names);
    }

    private function resolveDataAccessName(DataAccessInterface $dataAccess): string
    {
        return match (true) {
            $dataAccess instanceof ArrayDataAccess => DataAccess::ARRAY->value,
            $dataAccess instanceof PropertyDataAccess => DataAccess::PROPERTY->value,
            $dataAccess instanceof ReflectionDataAccess => DataAccess::REFLECTION->value,
            $dataAccess instanceof SetterDataAccess => DataAccess::SETTER->value,
            default => DataAccess::AUTODETECT->value,
        };
    }

    /**
     * @param string[] $path
     */
    private function parseObjectChildNode(string $name, mixed $value, string $dataAccess, array $path): ?Node
    {
        if (is_string($value)) {
            return new Node(
                name: $name,
                dataAccess: $dataAccess,
                nodeType: NodeType::LEAF,
                isList: false,
            );
        }

        if (is_array($value)) {
            $arrayNode = $this->parseArrayNode($name, $value, $path);
            if ($arrayNode === null) {
                return null;
            }

            return new Node(
                name: $name,
                dataAccess: $dataAccess,
                nodeType: $arrayNode->nodeType,
                isList: $arrayNode->isList,
                childNodes: $arrayNode->childNodes,
            );
        }

        if (is_object($value)) {
            $childTree = $this->parseObjectTree($value, $path);

            return new Node(
                name: $name,
                dataAccess: $dataAccess,
                nodeType: NodeType::NODE,
                isList: false,
                childNodes: $childTree->childNodes,
            );
        }

        return null;
    }

    /**
     * @param string[] $path
     */
    private function mergeChildNode(Node $target, Node $incoming, array $path): void
    {
        if ($target->nodeType === NodeType::LEAF && $incoming->nodeType === NodeType::NODE) {
            $target->nodeType = NodeType::NODE;
            $target->isList = $incoming->isList;
            $target->childNodes = $incoming->childNodes;

            return;
        }

        if ($target->nodeType === NodeType::NODE && $incoming->nodeType === NodeType::NODE) {
            $target->mergeChildrenFromNode($incoming, $path);
        }
    }
}
