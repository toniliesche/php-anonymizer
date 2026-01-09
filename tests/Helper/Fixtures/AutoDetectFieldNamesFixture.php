<?php

// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Test\Helper\Fixtures;

final class AutoDetectFieldNamesFixture
{
    public string $publicValue = 'public';

    private string $hidden = 'hidden';

    private readonly string $readonlyValue;

    /** @var array<string, string> */
    private array $data = [
        'magic' => 'magic',
    ];

    public function __construct()
    {
        $this->readonlyValue = 'readonly';
    }

    public function getMagic(): string
    {
        return $this->data['magic'];
    }

    public function setMagic(string $value): void
    {
        $this->data['magic'] = $value;
    }

    /**
     * @return array<string, string> $value
     */
    public function export(): array
    {
        return [
            'publicValue' => $this->publicValue,
            'hidden' => $this->hidden,
            'readonlyValue' => $this->readonlyValue,
            'magic' => $this->getMagic(),
        ];
    }
}
