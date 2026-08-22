# Changelog

## Unreleased

- Added `AnonymizerBuilder`, the unified façade operation contract, immutable
  object requests, and the object/extension module ports.

## 1.7.0

- Added `AnonymizerFactory::fromProfileProviders()` with deterministic provider
  flattening and existing profile validation semantics.

## 1.6.0

- Added validated profile governance metadata, deterministic profile-local rule
  fragments, canonical behavior fingerprints, and safe profile inspection.

## 1.5.0

- Added optional ASCII key normalization and profile-local global scalar rules.

## 1.4.0

- Added explicit document roots, document payload APIs, and lazy NDJSON
  anonymization with bounded safe audit aggregation.

## 1.3.0

- Added typed leaf transformations with validated registries and built-in
  `nullify`, `partial_mask`, `date_generalize`, and `numeric_bucket` handlers.

## 1.2.0

- Added contextual anonymization and replacement interfaces carrying scoped
  application attributes, profile, and path information.

## 1.1.0

- Added execution limits, lazy batch anonymization, and deterministic safe
  aggregate audit results.

## 1.0.0

- Initial stable release of the immutable, array-first anonymization core.
