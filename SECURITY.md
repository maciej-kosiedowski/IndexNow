# Security Policy

## Supported versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

Only the latest minor release of the current major version receives security fixes.

## Reporting a vulnerability

**Please do not open a public issue for security problems.**

Report vulnerabilities privately through GitHub Security Advisories:

<https://github.com/maciej-kosiedowski/IndexNow/security/advisories/new>

Please include:

* a description of the problem and its impact,
* the affected version(s),
* steps to reproduce, ideally a minimal reproducer.

You will get an acknowledgement within 7 days. Once a fix is ready a patch release is
published and the advisory is disclosed together with a credit, unless you prefer to stay
anonymous.

## Scope

This package sends HTTP requests to third-party search engine endpoints using an IndexNow
key that identifies your site. Two things are worth keeping in mind when assessing an issue:

* the IndexNow key is **not** a secret in the usual sense — the protocol requires it to be
  publicly readable at the configured `keyLocation`. It is an ownership proof, not a
  credential;
* the package never executes remote content — responses are only inspected for their HTTP
  status code and echoed (truncated) into exception messages.
