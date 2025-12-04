<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Exception;

use RuntimeException;

abstract class BaseAnonymizerException extends RuntimeException implements AnonymizerException
{
}
