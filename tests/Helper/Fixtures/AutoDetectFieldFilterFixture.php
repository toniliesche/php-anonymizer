<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class AutoDetectFieldFilterFixture
{
    public ?string $nullable = null;

    public string $unset;

    private readonly string $readonlyValue;

    /** @var array<string, object|string> */
    private array $data = [
        'fancy' => 'fancy',
        'only' => 'only',
    ];

    public function __construct()
    {
        $this->readonlyValue = 'readonly';
    }

    public function getFancy(): string
    {
        return $this->data['fancy'];
    }

    public function setFancy(string $value): void
    {
        $this->data['fancy'] = $value;
    }

    public function getOnly(): string
    {
        return $this->data['only'];
    }

    public function setItem(object $value): void
    {
        $this->data['item'] = $value;
    }

    public function get(): string
    {
        return 'ignored';
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return [
            'nullable' => $this->nullable,
            'unset' => $this->unset,
            'readonlyValue' => $this->readonlyValue,
            'fancy' => $this->getFancy(),
            'only' => $this->getOnly(),
        ];
    }
}
