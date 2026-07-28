# Architecture Boundaries

This project stays a Laravel modular monolith. The goal is not to split services or add framework
layers, but to keep business rules inside clear internal boundaries.

## Current Domains

- Transactions: transaction creation, numbering, validation, anomaly detection, duplicate checks,
  receipts, history, exports, and risk review.
- Periods: active zakat period resolution, annual defaults, chart windows, nisab, gold price, and
  period settings.
- Chatbot: public data answers, RAG retrieval, provider calls, guardrails, sentinel parsing,
  conversation context, logs, and feedback.
- Audit: audit log recording, searching, and review evidence.
- Muzakki: payer identity, contact data, and trash/restore behavior.
- Reporting: export shape and report-specific data access.

## Boundary Rules

- Controllers coordinate HTTP concerns only: authorize, validate, call one service, return a
  response.
- Cross-domain reads should go through a service or a small query object when the query has
  business meaning.
- Services may depend inward on models and value objects, but should not reach into another
  domain's implementation details casually.
- Transaction code generation, period defaults, risk scoring, and chatbot calculation routing are
  core business rules and should not live in Blade, controllers, or ad hoc route closures.
- Transaction write orchestration belongs in `TransactionSyncService`; `ZakatService` should stay a
  thin application service for request-level coordination, validation, review sync, and event
  dispatch.
- Provider integrations belong behind provider classes. The rest of the app should not know API
  payload details.
- Data-cleanup migrations that cannot be rolled back must say why in `down()` and be covered by the
  migration policy test allowlist.

## Refactor Priority

1. Keep `Transactions` as the strongest boundary because it controls money, receipts, auditability,
   and operational correctness.
2. Keep `Periods` separate from transactions so active-year/default logic does not spread across
   forms, validators, and reports.
3. Keep `Chatbot` isolated from transaction writes. It may read public aggregate data and knowledge
   base entries, but calculation output should stay explanatory unless an explicit deterministic
   workflow is added.
4. Move new complex report queries into `app/Services/Reporting` or dedicated query classes instead
   of adding them directly to controllers.

## Migration Policy

- New schema migrations should implement a real `down()` whenever Laravel can reverse the operation
  safely.
- Data-cleanup migrations may be irreversible, but the `down()` method must contain an explicit
  `Irreversible data cleanup` comment.
- Do not edit old production-run migrations to alter schema history. Add a new migration for future
  corrections.
- Before deployment, run `php artisan migrate:status` and keep a database backup when a migration
  changes or removes data.
