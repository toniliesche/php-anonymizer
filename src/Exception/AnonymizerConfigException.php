<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Exception;

final class AnonymizerConfigException extends BaseAnonymizerException
{
    public static function missingCustomSerializer(): self
    {
        return new self('Missing required config value for anonymizer.serializer.custom_serializer for serializer mode "custom".');
    }
}
