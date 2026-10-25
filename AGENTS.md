# Consho Licence Check — rules for agents

Applies to every coding agent working in this repository. Tool-specific files (such as `CLAUDE.md`) only import
this file, so that there is **one** version.

## What this is

A free package (MIT, L18) for the **customer's installation**: it checks the licence key and the daily signed
confirmation for every paid module that registers with it — Consho Connect, the licensing package, and equally
extensions by other vendors that sell through a Consho shop of their own (L19). The counterpart in the selling shop
is `webwerkwien/contao-consho-licensing-bundle` (proprietary).

**Does not depend on the Consho core**, only on Contao — so that any Contao extension can use it.

**The specification lives in the core's private development repository** (`contao-consho-bundle-dev`,
`docs/block-lizenz.md`: block licence, section 4.5, decisions L18, L19, P1, P2, plan LZ-6) and is not published.
With access: read it before every change and update its line after every plan. Without access, README and code
apply.

## Principles

- **Agent-neutral:** no instructions, paths or file names tied to a particular tool.
- **Public files carry no internals, and they are English.** Everything that ships with the package or the
  repository — code, comments, tests, test data, configuration, README, this file, CHANGELOG, commit messages, tag
  and release texts — names no people, customers or testers (the copyright in LICENSE and `composer.json` stays),
  no notes, plans or other documents outside the repository, no servers, hosts, SSH users or server paths, no real
  test data (order numbers, e-mail addresses — use `example.org`/`example.net`), no keys or key ids, no review or
  acceptance shorthands, sessions, agents or models. A reason goes into the comment as a statement of fact. Block
  references such as "(LZ-6)" stay. Comments, docblocks and docs are English, also in tests and also where a file
  still has German ones; language files and German UI texts stay German. The CHANGELOG (and with it the GitHub
  release note that copies it) says only what changes for operators. Removing internals is not announced.
- **Trusted public keys come from the module's code** (P1), never from the installation's configuration —
  otherwise a customer would enter a pair of their own.
- **No copy protection** (L4): PHP is readable. The checker keeps honest customers from misunderstandings and makes
  the contract visible; `APP_ENV=dev` locks nothing.
- **Never the key in output that can end up in a chat** — not in contao-ai answers, not in logs. It is entered
  hidden on the console or in the back end.
- **Only key, installation id and version are sent** (L8, LZ-3 E1) — no domain, no shop data.

## Language and style

- Code comments, test comments and commit messages in English; test method names are German.
- Class comments explain decisions, not the obvious.

## Before every commit

```bash
vendor/bin/phpunit --no-coverage
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
```

Both green, the whole suite. Mutation checks (mutate a copy of the file, the test must turn red, copy it back —
never `git checkout`/`restore`/`stash`/`reset`) wherever a fault would make a lock ineffective, a forgery
acceptable or a key visible. Commits with explicit paths.
