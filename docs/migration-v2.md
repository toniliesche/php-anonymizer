# Core 2.0 migration extract

Replace `AnonymizerFactory` and direct `ArrayAnonymizer` construction with
`AnonymizerBuilder::create()->...->build()`. Use the resulting
`AnonymizerInterface` for array, payload, document, batch, audit, and NDJSON
operations. Internal registries, compilers, and traversal services have no
supported replacement.

See the [complete 2.0 migration guide](https://github.com/toniliesche/php-anonymizer-monorepo/blob/main/docs/migration-v2.md).
