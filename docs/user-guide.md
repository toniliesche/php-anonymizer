# Core package guide

Install `php-anonymizer/anonymizer` in a PHP 8.5+ application with
`ext-mbstring`. The core has no framework requirement. It applies named
profiles to associative arrays and encoded documents through one immutable
builder and reusable façade.

## First anonymization

Create and register a profile before calling it by name:

<!-- executable -->
```php
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

$profile = ProfileDefinition::fromArray('customer', [
    'mode' => 'redact',
    'rules' => [['field' => 'email']],
]);
$anonymizer = AnonymizerBuilder::create()->withProfile($profile)->build();
$result = $anonymizer->anonymize('customer', ['email' => 'ada@example.test']);

if ($result !== ['email' => '[REDACTED]']) {
    throw new RuntimeException('The core guide example failed.');
}
```

`redact` is the default mode. In `preserve` mode, selected strings remain as
provided and other strings are redacted recursively. Profiles and builders are
immutable; each registration method returns a new builder.

## Choose the input operation

Use `anonymize()` for an associative array. Use `anonymizePayload()` for an
encoded map, such as JSON. Use `anonymizeDocument()` for a map or for a list
root explicitly declared as `root: list` in the profile. The JSON codec is
included in the core package.

`anonymizeRecords()` yields associative-array records as they are requested.
`anonymizeNdjson()` returns a result whose `lines()` iterable lazily processes
logical JSON lines. Errors from later records appear during iteration, and the
stream audit is available only after successful full consumption.

## Profiles and reports

Use `children` to select nested map fields and `each` to select list elements.
Conditions inspect original sibling values. A report-enabled operation
returns transformed data, anonymized JSON Pointer paths, and selected paths
that were missing from the input.

The complete [profile schema reference](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/docs/profile-schema.md)
covers modes, traversal, transformations, fragments, key normalization,
global rules, limits, and validation. The [full user guide](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/docs/user-guide.md)
contains examples for payloads, streams, reports, and audits.

## Extend the core

Custom strategies, payload codecs, transformations, profile providers,
extension modules, and object adapters implement the public interfaces under
`PhpAnonymizer\Anonymizer\Application\Extension`. Register them explicitly
with `AnonymizerBuilder`; the core does not scan a container or filesystem.
The [extension author guide](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/docs/extension-author-guide.md)
documents each port and its registration contract.

Execution limits are enabled by default: 10 MiB per encoded payload, depth 64,
100,000 visited nodes, 50,000 entries per collection, and embedded depth 8.
Override them by registering `AnonymizationLimits` with
`AnonymizerBuilder::withLimits()`.

Object anonymization requires the optional Symfony Serializer adapter and an
application-provided Serializer. The core does not normalize objects itself.
For breaking changes from the previous API, read the
[2.0 migration extract](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/packages/anonymizer/docs/migration-v2.md).
