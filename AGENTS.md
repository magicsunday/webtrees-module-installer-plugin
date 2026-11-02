# webtrees-module-installer-plugin — agent notes

This file collects contributor conventions for the
`magicsunday/webtrees-module-installer-plugin` repository.

## Git flow

- Commit subjects — and the pull-request title — are governed by the shared `commit-convention` gate; the normative rule and its full rationale live in `magicsunday/.github/.github/workflows/commit-convention.yml@main`, which self-tests a decision table before applying it. In short: a `GH-`-prefixed subject must match `^GH-\d+: [A-Z]`, every other subject `^[A-Z]` — a capitalised English imperative — and conventional-commit prefixes (`feat:`, `Fix:`, …) as well as path-like starts (`src/…: …`) are rejected whatever their case. It runs on every pull request via `.github/workflows/commit-lint.yml`, advisory until `commit-convention / Commit convention` is a required context in branch protection.
- Branches for an issue are named exactly `GH-<N>`; the `GH-<N>: ` prefix marks work that belongs to that issue, so a drive-by fix on the branch keeps its own unprefixed subject.
- The pull-request body closes the issue with `Closes #<N>` — the `GH-<N>: ` subject prefix is not a GitHub link and closes nothing.
- Never add a `Co-Authored-By:` trailer or any other AI attribution.

## Tests

- `tests/Composer` mirrors `src/Composer` and tests the installer and the plugin with a stubbed Composer.
- `tests/EndToEnd` runs the real Composer of the development dependencies in a scratch project that is built from local path repositories, so it needs no network. It checks the modules directory on the file system after install, update and removal. It is the only suite that sees what the plugin writes and deletes.

<!-- testing-policy:begin v1 -->
## Testing policy

Tests protect behavior whose regression would be materially incorrect, unsafe,
destructive or hard to notice before release. They are not a coverage target and
they are not a mutation-score target.

Protect these with durable tests:
- security and privacy boundaries, including untrusted data reaching a path,
  archive member, URL, header, query, command or DOM sink
- confidentiality outside access checks (secrets or personal data in logs,
  exceptions, temporary files, caches, exports, URLs)
- data integrity across import, export, update and migration
- lifecycle and failure semantics: atomic writes, rollback, cleanup, idempotency,
  retries, partial failure, and ordering where the order is the protocol
- calculation and decision rules, including their boundaries
- compatibility boundaries: public API, serialized formats, extension interfaces,
  persisted configuration keys, producer and consumer contracts
- every fixed behavioral bug (some test must fail on the old behavior). A bug
  that is only a wrong label, CSS class or default is not behavioral unless a
  named consumer breaks on it.

For side effects assert the postcondition and the identity of the affected
resource, not only that a collaborator was called.

Do not add tests whose only content is:
- a getter, setter, constructor that only stores values, DTO, enum or constant,
  unless it carries a persisted key or a serialized format from the list above
- a CSS class list, a label or message text, or a default value, unless the exact
  value is a documented interface that a named consumer breaks on. Prefer testing
  the consuming behavior over the producer literal and pin only the minimum literal.
- transparent delegation that adds no argument selection, ordering, error
  translation or security context
- the text of source files instead of the behavior they produce

Order and default assertions are fine when they encode a safety property, for
example authorize before the side effect, write before rename, private unless
made public.

A test that a reviewer, human or agent, questions must show the observable
behavior or invariant it protects, a plausible faulty change it rejects, and for
a pinned literal the consumer that would break. Mutating the code or reverting
the fix and watching the test fail is the way to show it.
<!-- testing-policy:end -->
