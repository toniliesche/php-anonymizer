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

The profile schema supports preserve mode, conditions, scalar fallbacks,
strategies, and embedded payloads. Extension contracts are the public
interfaces under `Application/Extension` and do not require a framework.
