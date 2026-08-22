# PHP Anonymizer Core

Pure PHP array anonymization with immutable profile definitions. The core
supports flat and nested string redaction, preserve-mode allow-lists, and
conditional rules without mutating the input array. Named replacement
strategies and encoded JSON payloads are supported through framework-free
extension ports.

```php
$profile = ProfileDefinition::fromArray('customer', [
    'mode' => 'redact',
    'rules' => [
        ['field' => 'customer', 'children' => [['field' => 'email']]],
        ['field' => 'orders', 'each' => [['field' => 'reference']]],
        ['field' => 'tags', 'each' => true],
    ],
]);

$result = $anonymizer->anonymizeWithReport('customer', [
    'customer' => ['email' => 'a@example.com'],
    'orders' => [['reference' => 'A-1']],
    'tags' => ['internal'],
]);

$result->data();              // redacted array
$result->anonymizedPaths();   // ['/customer/email', '/orders/0/reference', '/tags/0']
$result->missingPaths();      // []
```

`AnonymizerInterface` is the single public service contract. Use
`AnonymizerBuilder::create()` with `withProfiles()` or
`withProfileProviders()` when constructing the core outside Symfony; concrete
engines and profile collections remain implementation details. Providers must
be registered explicitly and return immutable profile definitions.

`children` traverses an associative map, while `each` traverses a PHP list.
`each: true` redacts every string list element; `each: [rules]` traverses list
elements as maps. Wrong runtime shapes and selected non-string values fail with
path-aware exceptions. Report and exception paths use RFC 6901 JSON Pointer
notation, so `/` is encoded as `~1` and `~` as `~0`.

The profile schema supports preserve mode, conditions, scalar fallbacks,
strategies, embedded payloads, comparison-only key normalization, and global
scalar rules. Extension contracts are the public interfaces under
`Application/Extension` and do not require a framework.

Profiles may optionally carry validated governance metadata and ordered,
profile-local reusable fragments. Fragment expansion is performed when the
profile is compiled; it does not evaluate expressions at runtime. Use
`ProfileInspectorInterface` when an application needs a safe summary containing
the profile name, metadata, behavior fingerprint, and fragment names. Raw rules,
source values, replacements, and secrets are never part of inspection or audit
output.

Global rules can target a named scalar at every map depth, including maps in
lists, while retaining the original key spelling:

```php
$profile = ProfileDefinition::fromArray('customer', [
    'mode' => 'redact',
    'key_normalization' => true,
    'global_rules' => [['field' => 'email_address']],
]);
```

See the package-local [user guide](docs/user-guide.md) and
[2.0 migration extract](docs/migration-v2.md).
