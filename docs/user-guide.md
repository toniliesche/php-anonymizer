# Core package guide

Install `php-anonymizer/anonymizer` and construct the immutable façade with
`AnonymizerBuilder`. The core handles arrays, map payloads, explicit document
roots, batches, reports, audits, and NDJSON. Use the repository
[`user guide`](../../../docs/user-guide.md) for the full application walkthrough.

The core profile vocabulary is maintained in
[`profile-schema.md`](../../../docs/profile-schema.md), and framework-free
extension ports are described in
[`extension-author-guide.md`](../../../docs/extension-author-guide.md).

For a complete plain-PHP run, combine a list profile with batch and NDJSON
operations:

<!-- executable -->
```php
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

$profile = ProfileDefinition::fromArray('records', ['mode' => 'redact', 'root' => 'list', 'rules' => [['field' => 'email']]]);
$anonymizer = AnonymizerBuilder::create()->withProfile($profile)->build();
$records = iterator_to_array($anonymizer->anonymizeRecords('records', [['email' => 'one@example.test']]), false);
$lines = iterator_to_array($anonymizer->anonymizeNdjson('records', ['{"email":"one@example.test"}'])->lines(), false);

if ($records !== [['email' => '[REDACTED]']] || $lines !== ['{"email":"[REDACTED]"}']) {
    throw new RuntimeException('The core batch example failed.');
}
```
