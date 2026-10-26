# PHP Anonymizer Core

Pure PHP array anonymization with reusable profiles. The core supports nested
map and list traversal, conditional rules, named replacement strategies,
encoded documents, batches, and safe reports and audits. It requires PHP 8.5
and `ext-mbstring`.

### Project Status

[![Tests](https://github.com/toniliesche/php-anonymizer-monorepo/actions/workflows/quality.yml/badge.svg)](https://github.com/toniliesche/php-anonymizer-monorepo/actions/workflows/quality.yml)
[![Code Coverage](https://codecov.io/gh/toniliesche/php-anonymizer-monorepo/branch/develop/graph/badge.svg)](https://codecov.io/gh/toniliesche/php-anonymizer-monorepo)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Ftoniliesche%2Fphp-anonymizer-monorepo%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/toniliesche/php-anonymizer-monorepo/main)
[![Downloads](https://img.shields.io/packagist/dt/php-anonymizer/anonymizer.svg)](https://packagist.org/packages/php-anonymizer/anonymizer)
[![Stars](https://img.shields.io/github/stars/toniliesche/php-anonymizer-monorepo)](https://github.com/toniliesche/php-anonymizer-monorepo/stargazers)

### Project Info

[![PHP Version](https://img.shields.io/packagist/php-v/php-anonymizer/anonymizer)](https://github.com/toniliesche/php-anonymizer-monorepo)
[![Latest Version](https://img.shields.io/packagist/v/php-anonymizer/anonymizer.svg)](https://packagist.org/packages/php-anonymizer/anonymizer)
[![License](https://img.shields.io/github/license/toniliesche/php-anonymizer-monorepo)](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/LICENSE)

### Repo Status

[![Last Commit](https://img.shields.io/github/last-commit/toniliesche/php-anonymizer-monorepo)](https://github.com/toniliesche/php-anonymizer-monorepo)
[![Issues](https://img.shields.io/github/issues/toniliesche/php-anonymizer-monorepo)](https://github.com/toniliesche/php-anonymizer-monorepo/issues)

### License

This project is released under the MIT license.

See the [LICENSE](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/LICENSE) for more information.

## Install

```sh
composer require php-anonymizer/anonymizer
```

## Quick start

Profiles are registered with an immutable builder. The returned façade can be
reused for independent inputs:

<!-- executable -->
```php
use PhpAnonymizer\Anonymizer\Application\Anonymization\AnonymizerBuilder;
use PhpAnonymizer\Anonymizer\Domain\Profile\ProfileDefinition;

$profile = ProfileDefinition::fromArray('customer', [
    'mode' => 'redact',
    'rules' => [['field' => 'email']],
]);
$anonymizer = AnonymizerBuilder::create()->withProfile($profile)->build();
$safe = $anonymizer->anonymize('customer', ['email' => 'ada@example.test']);

if ($safe !== ['email' => '[REDACTED]']) {
    throw new RuntimeException('The core quick-start example failed.');
}
```

In `preserve` mode, selected strings remain unchanged and other strings in the
input tree are redacted. See the [user guide](docs/user-guide.md) for the
complete walkthrough and the [profile schema](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/docs/profile-schema.md)
for rule syntax, matching behavior, and validation.

## Core capabilities

- `anonymize()` transforms associative arrays without changing their shape.
- `anonymizePayload()` handles encoded maps; `anonymizeDocument()` also
  supports explicitly declared list roots.
- `anonymizeRecords()` and `anonymizeNdjson()` process records lazily.
- `anonymizeWithReport()` and `anonymizeWithAudit()` return paths or aggregate
  operational metadata without source or replacement values.
- Strategies, codecs, transformations, and profile providers use the public,
  framework-free extension interfaces.

## Packages

The PHP Anonymizer project is split into packages for core anonymization and
optional integrations:

- [`php-anonymizer/anonymizer`](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer) — framework-independent core library.
- [`php-anonymizer/anonymizer-bundle`](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-bundle) — Symfony Bundle integration.
- [`php-anonymizer/anonymizer-faker`](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-faker) — Faker-powered replacement strategies.
- [`php-anonymizer/anonymizer-laravel`](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-laravel) — Laravel integration.
- [`php-anonymizer/anonymizer-personas`](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-personas) — reusable persona data for anonymized fixtures.
- [`php-anonymizer/anonymizer-pseudonymization`](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-pseudonymization) — pseudonymization strategies.
- [`php-anonymizer/anonymizer-symfony-serializer`](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-symfony-serializer) — optional Symfony Serializer object adapter.

The builder does not scan for profiles or extensions. Register each profile,
provider, or extension explicitly. For framework setup, install the
[Symfony Bundle](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-bundle)
or [Laravel integration](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-laravel).
Object anonymization is available through the optional
[Symfony Serializer adapter](https://github.com/toniliesche/php-anonymizer-monorepo/tree/main/packages/anonymizer-symfony-serializer).

Read the [extension author guide](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/docs/extension-author-guide.md)
to implement custom ports. For changes from the previous API, see the
[2.0 migration guide](docs/migration-v2.md).
