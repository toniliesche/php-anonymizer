# Changelog

## Unreleased

## 2.0.1

## 2.0.0

- Iteration 29: validated extension registry entries at runtime and removed
  unsafe iterable type claims from registry and provider registration paths.
- Iteration 26: compiled immutable exact and normalized traversal lookups,
  centralized snapshot-scoped rule selection, and honored conditions on global
  rules while preserving output and report semantics.
- Centralized runtime assembly behind the immutable builder while preserving
  the internal direct `ArrayAnonymizer` compatibility path.
- Added stable string-path accessors for contextual replacement and
  transformation values, plus provider-free NDJSON result construction.
- Iteration 22: extracted `RuleIndex` and the deferred NDJSON audit provider,
  renamed and reorganized structural tests, and consolidated reusable test
  extension fixtures without changing anonymization behavior.
- Selectively removed coverage suppressions from JSON payload validation and
  transformation registration/option compilation, with focused validation
  tests and stricter string-keyed transformation options.
- Removed the transitional `AnonymizerFactory`; use `AnonymizerBuilder` for
  fluent construction and extension modules.
- Added `AnonymizerBuilder`, the unified façade operation contract, immutable
  object requests, and the object/extension module ports.
- Added the core user and migration documentation for the 2.0 public boundary.
- Added runnable profile-schema and façade examples for fragments, normalized
  global rules, list documents, batches, and NDJSON.
- Documented the complete core profile grammar, recursive traversal forms,
  fragment composition, and rule validation behavior.

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
