# Contributing

Thanks for taking the time to contribute!

## Getting started

```bash
git clone https://github.com/maciej-kosiedowski/IndexNow.git
cd IndexNow
composer install
```

A coverage driver (`pcov` or `xdebug`) is required for the coverage and mutation
testing steps; the plain test suite runs without one.

## Quality gate

Every pull request has to pass the same gate that CI runs:

```bash
composer cs         # coding standards (PHP-CS-Fixer, PSR-12 based)
composer phpstan    # static analysis, PHPStan level 10 + strict rules
composer test       # PHPUnit
composer infection  # mutation testing, 100% MSI required
```

or simply:

```bash
composer ci
```

`composer cs:fix` rewrites the files that violate the coding standard.

To run a single test file or a single test:

```bash
vendor/bin/phpunit tests/Unit/Job/SubmitJobTest.php
vendor/bin/phpunit --filter testSplitsLargeQueuesIntoBatches
```

## Non-negotiables

This package is a dependency of other people's production systems, so a few rules are
enforced by CI and cannot be waived:

* **100% line coverage and 100% MSI.** New behaviour needs tests that actually pin it
  down. If a mutant survives, the test is not specific enough.
* **PHPStan level 10 with strict rules**, and no baseline. Fix the type, do not silence
  the error.
* **No new runtime dependencies** without a discussion first. The whole point of the
  package is that it only needs PSR-17/PSR-18.
* **Semantic versioning.** Anything that changes a public signature, a thrown exception
  type or the shape of the submitted payload is a breaking change.

## Pull requests

1. Branch off `master`.
2. Keep the change focused; unrelated refactors belong in their own pull request.
3. Add a `CHANGELOG.md` entry under `## [Unreleased]`.
4. Make sure `composer ci` is green locally before pushing.

## Reporting bugs

Open an issue with the package version, the PHP version, the PSR-18 client in use and a
minimal reproducer. Security problems go through the process described in
[SECURITY.md](SECURITY.md) instead.
