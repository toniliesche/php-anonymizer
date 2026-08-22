<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

interface NdjsonAuditProviderInterface
{
    public function audit(): NdjsonAuditResult;

    public function complete(NdjsonAuditResult $audit): void;
}
