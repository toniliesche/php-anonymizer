<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Extension\Profile;

interface ProfileInspectorInterface
{
    public function inspect(string $name): ?ProfileInspection;

    /**
     * @return iterable<ProfileInspection>
     */
    public function profiles(): iterable;
}
