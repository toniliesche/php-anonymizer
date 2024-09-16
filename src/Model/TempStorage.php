<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Model;

class TempStorage
{
    /** @var array<string,mixed> */
    private array $data = [];

    public function store(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function retrieve(string $key): mixed
    {
        return $this->data[$key];
    }
}
