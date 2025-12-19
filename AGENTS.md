# Repository Guidelines

## Project Structure & Module Organization
- Library code lives in `src/` under the `PhpAnonymizer\Anonymizer` namespace. Encoders, rule parsers, and providers are grouped by feature folders.
- Automated examples are in `examples/` (bootstrap helpers in `examples/includes/`), useful for quick reference and manual validation.
- Tests sit in `tests/` with `Unit/` and `Integration/` suites configured via `phpunit.xml`.
- Config and tooling baselines: `anonymizer.dist.yaml` (sample rules), `phpstan.dist.neon`, `phpmd.xml`, `rector.php`, and `phpmd.baseline.xml`. Generated coverage lives in `.coverage/` (git-ignored).

## Build, Test, and Development Commands
- Install deps: `composer install` (PHP ≥8.2). Run once per clone or after dependency updates.
- Fast checks before commits: `make commit-checks` (php-cs-fixer check, rector dry-run, PHPStan, PHP Mess Detector, require/audit checks, tests).
- Full CI parity: `make push-checks` or `make quality-of-code` for the entire static/test stack.
- Targeted tasks: `vendor/bin/phpunit --testsuite Unit|Integration`, `vendor/bin/phpstan`, `vendor/bin/phpmd src,tests text phpmd.xml`, `vendor/bin/php-cs-fixer check src tests`, `vendor/bin/rector process src tests --dry-run`.
- Coverage: `make test-coverage` (HTML in `.coverage/`); mutation testing: `make mutation-tests`.

## Coding Style & Naming Conventions
- Follow PSR-12: 4-space indentation, strict types on new files, and descriptive class/method names aligned with domain language.
- Keep one public class per file; favor immutable value objects and explicit return types.
- Use `php-cs-fixer` rules (see `.php-cs-fixer.dist.php` if present) and `rector.php` for refactors. Run `make fix-style` or `make rector-fix` when adjusting style at scale.
- Namespace paths mirror `src/` folders; tests mirror source structure under `tests/`.

## Testing Guidelines
- Prefer fast unit coverage; place integration tests under `tests/Integration` and mark suites in `phpunit.xml`.
- Name tests after behavior, e.g., `RuleSetParserTest`, and keep fixtures close to the test folder.
- Run `vendor/bin/phpunit` before pushing; add snapshots/fixtures when anonymization output matters.
- Ensure new behaviors include edge-case coverage (empty payloads, nested structures, serializer variations).

## Commit & Pull Request Guidelines
- Commit messages follow Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, etc.). Keep subject lines imperative and scoped.
- Before opening a PR: run `make commit-checks` at minimum; include a short summary of changes, testing commands/results, and any configuration impacts (e.g., new encoder options).
- Link related issues; add screenshots or sample payloads only when they clarify behavior changes.
- For release work, use the version helpers in `Makefile` (`update-*`, `print-version`, `build-%`) and tag via `make build-<type>` when appropriate.
