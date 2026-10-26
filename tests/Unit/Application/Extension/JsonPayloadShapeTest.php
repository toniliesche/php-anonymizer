<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Tests\Unit\Application\Extension;

use JsonException;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadCodec;
use PhpAnonymizer\Anonymizer\Application\Extension\Payload\JsonPayloadData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonPayloadCodec::class)]
#[CoversClass(JsonPayloadData::class)]
final class JsonPayloadShapeTest extends TestCase
{
    public function testMapShapeCanBeDecodedRestoredAndInvalidated(): void
    {
        $codec = new JsonPayloadCodec();
        $shape = $codec->decodeWithShape('{"nested":{},"list":[]}');

        self::assertSame(['nested' => [], 'list' => []], $shape->data());
        self::assertSame('{"nested":{},"list":[]}', $codec->encodeWithShape(['nested' => [], 'list' => []], $shape, []));
        self::assertSame('{"nested":[],"list":[]}', $codec->encodeWithShape(['nested' => [], 'list' => []], $shape, ['/nested']));

        $nested = $codec->decodeWithShape('{"outer":{"inner":{}}}');
        self::assertSame('{"outer":{"inner":[]}}', $codec->encodeWithShape(['outer' => ['inner' => []]], $nested, ['/outer']));

        try {
            JsonPayloadData::decodeMap('[]');
            self::fail('List roots are not map payloads.');
        } catch (JsonException) {
        }

        try {
            JsonPayloadData::decodeMap('[1]');
            self::fail('Non-empty list roots are not map payloads.');
        } catch (JsonException) {
        }

        $this->expectException(JsonException::class);
        JsonPayloadData::decodeMap('{}');
    }

    public function testDocumentShapeSupportsEmptyObjectAndEmptyArrayRoots(): void
    {
        $codec = new JsonPayloadCodec();
        $object = $codec->decodeDocumentWithShape('{}');
        $list = $codec->decodeDocumentWithShape('[]');

        self::assertSame('{}', $codec->encodeDocumentWithShape($object->data(), $object, []));
        self::assertSame('[]', $codec->encodeDocumentWithShape($list->data(), $list, []));
        self::assertSame('[]', $codec->encodeDocumentWithShape($object->data(), $object, ['']));
        self::assertSame([1], $codec->decodeDocument('[1]'));
        self::assertSame('[1]', $codec->encodeDocument([1]));
        self::assertSame(['value' => 1], $codec->decode('{"value":1}'));
        self::assertSame('{"value":1}', $codec->encode(['value' => 1]));

        $this->expectException(JsonException::class);
        JsonPayloadData::decodeDocument('null');
    }

    public function testShapeMetadataUsesEscapedPointersAndExactInvalidationBoundaries(): void
    {
        $codec = new JsonPayloadCodec();
        $shape = $codec->decodeWithShape('{"outer":{},"outermost":{},"a/b~c":{}}');

        /** @var array<string, mixed> $data */
        $data = $shape->data();

        self::assertSame('{"outer":[],"outermost":{},"a\/b~c":[]}', $codec->encodeWithShape($data, $shape, ['/outer', '/a~1b~0c']));
        self::assertSame('{"outer":{},"outermost":{},"a\/b~c":{}}', $codec->encodeWithShape($data, $shape, []));
    }
}
