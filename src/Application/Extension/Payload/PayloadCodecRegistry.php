<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Payload;

use InvalidArgumentException;
use function Safe\preg_match;

// @internal
final readonly class PayloadCodecRegistry
{
    /** @var array<string, PayloadCodecInterface> */
    private array $codecs;

    /**
     * @param iterable<PayloadCodecInterface> $codecs
     */
    public function __construct(iterable $codecs)
    {
        $registered = [];
        foreach ($codecs as $codec) {
            $id = $codec->id();
            if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $id) !== 1) {
                throw new InvalidArgumentException(sprintf('The payload codec ID "%s" is invalid.', $id));
            }
            if (isset($registered[$id])) {
                throw new InvalidArgumentException(sprintf('Payload codec "%s" is registered more than once.', $id));
            }

            $registered[$id] = $codec;
        }

        $this->codecs = $registered;
    }

    public function get(string $id): ?PayloadCodecInterface
    {
        return $this->codecs[$id] ?? null;
    }

    /**
     * @return list<PayloadCodecInterface>
     */
    public function all(): array
    {
        return array_values($this->codecs);
    }
}
