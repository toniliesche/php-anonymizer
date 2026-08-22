<?php

declare(strict_types=1);

namespace PhpAnonymizer\Anonymizer\Application\Anonymization\Result;

use LogicException;
use Override;

/**
 * @internal lazy state holder for NDJSON audit results
 *
 * @codeCoverageIgnore Internal operation wiring.
 */
final class DeferredNdjsonAuditProvider implements NdjsonAuditProviderInterface
{
    private ?NdjsonAuditResult $result = null;

    #[Override]
    public function audit(): NdjsonAuditResult
    {
        if (!$this->result instanceof NdjsonAuditResult) {
            throw new LogicException('NDJSON audit is available only after successful stream exhaustion.');
        }

        return $this->result;
    }

    #[Override]
    public function complete(NdjsonAuditResult $audit): void
    {
        $this->result = $audit;
    }
}
