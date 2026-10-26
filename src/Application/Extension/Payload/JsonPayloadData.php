<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Payload;

use JsonException;
use stdClass;
use Webmozart\Assert\Assert;
use function Safe\json_encode;

/**
 * @internal call-local decoded JSON data and original object-container shape
 */
final readonly class JsonPayloadData
{
    /**
     * @param array<mixed, mixed> $data
     * @param array<string, string> $objectPaths
     */
    private function __construct(private array $data, private array $objectPaths)
    {
    }

    /**
     * @throws JsonException
     */
    public static function decodeMap(string $payload): self
    {
        $decoded = json_decode($payload, false, flags: JSON_THROW_ON_ERROR);
        if (!$decoded instanceof stdClass) {
            throw new JsonException('JSON payload root must be an object.');
        }

        $paths = [];
        $data = self::toArray($decoded, '', $paths);
        Assert::isArray($data);
        if ($data === []) {
            throw new JsonException('JSON payload root must be an object.');
        }

        return new self($data, $paths);
    }

    /**
     * @throws JsonException
     */
    public static function decodeDocument(string $payload): self
    {
        $decoded = json_decode($payload, false, flags: JSON_THROW_ON_ERROR);
        if (!$decoded instanceof stdClass && !is_array($decoded)) {
            throw new JsonException('JSON document root must be an object or array.');
        }

        $paths = [];
        $data = self::toArray($decoded, '', $paths);
        Assert::isArray($data);

        return new self($data, $paths);
    }

    /**
     * @return array<mixed, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @param array<mixed, mixed> $data
     * @param list<string> $invalidatedPaths
     */
    public function encode(array $data, array $invalidatedPaths): string
    {
        return json_encode(self::restore($data, '', $this->objectPaths, $invalidatedPaths), JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string> $objectPaths
     */
    private static function toArray(mixed $value, string $path, array &$objectPaths): mixed
    {
        if ($value instanceof stdClass) {
            $objectPaths[$path] = $path;
            $result = [];
            foreach (get_object_vars($value) as $key => $child) {
                $result[$key] = self::toArray($child, self::append($path, (string) $key), $objectPaths);
            }

            return $result;
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $child) {
                $result[$key] = self::toArray($child, self::append($path, (string) $key), $objectPaths);
            }

            return $result;
        }

        return $value;
    }

    /**
     * @param array<string, string> $objectPaths
     * @param list<string> $invalidatedPaths
     */
    private static function restore(mixed $value, string $path, array $objectPaths, array $invalidatedPaths): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $wasObject = isset($objectPaths[$path]) && !self::isInvalidated($path, $invalidatedPaths);
        if (array_is_list($value) && !$wasObject) {
            $result = [];
            foreach ($value as $key => $child) {
                $result[] = self::restore($child, self::append($path, (string) $key), $objectPaths, $invalidatedPaths);
            }

            return $result;
        }

        $object = new stdClass();
        foreach ($value as $key => $child) {
            $object->{(string) $key} = self::restore($child, self::append($path, (string) $key), $objectPaths, $invalidatedPaths);
        }

        return $object;
    }

    /**
     * @param list<string> $invalidatedPaths
     */
    private static function isInvalidated(string $path, array $invalidatedPaths): bool
    {
        return array_any($invalidatedPaths, static fn (string $invalidated): bool => $path === $invalidated || str_starts_with($path, $invalidated . '/'));
    }

    private static function append(string $path, string $key): string
    {
        $escaped = str_replace(['~', '/'], ['~0', '~1'], $key);

        return $path . '/' . $escaped;
    }
}
