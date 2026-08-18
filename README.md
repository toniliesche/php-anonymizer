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

`children` traverses an associative map, while `each` traverses a PHP list.
`each: true` redacts every string list element; `each: [rules]` traverses list
elements as maps. Wrong runtime shapes and selected non-string values fail with
path-aware exceptions. Report and exception paths use RFC 6901 JSON Pointer
notation, so `/` is encoded as `~1` and `~` as `~0`.

See the repository README and iteration plan for the complete roadmap.
See [`docs/profile-schema.md`](../../docs/profile-schema.md) for preserve-mode,
condition, scalar-fallback, strategy, and embedded-payload details. See
[`docs/extension-author-guide.md`](../../docs/extension-author-guide.md) for
custom strategy and codec contracts.
