# Scheduling tests — coverage boundaries

Honest limits of the current suite (not a blocker list).

## Concurrency

- **Double-hold** tests assert overlap detection when the second request runs **after** the first commit (sequential). They do **not** prove two concurrent DB transactions on MySQL/PostgreSQL under production load.
- **Optional later:** parallel clients against **MySQL/Postgres** for `createHold` if CI gains a second DB job; not required while SQLite-only CI is stable.

## Filament / UI

- `TenantSchedulingFilamentAccessTest` covers **tenant scoping**, **IDOR-style edit URLs**, **smoke** on scheduling pages, and **module disabled** (no 500). It does not exhaust every Filament action, Livewire edge case, or entitlement banner copy.
- `TenantSchedulingCalendarIntegrationsGatingTest` covers **`calendar_integrations_enabled = false`** while scheduling stays on: **gating banner** text on key pages and **calendar list/create/edit** without 5xx (separate gate from `scheduling_module_enabled`).
- `PlatformSchedulingBookableServiceAccessTest`: platform **list scope** (no tenant rows), **create** smoke, and **platform default** `WriteCalendarResolver` path without tenant.

## Occupancy preview

- `SchedulingOccupancyPreviewTest` locks the **contract** of `SchedulingOccupancyPreviewService`: internal vs external buckets, **target narrowing** for internal rows, **tentative** on external.
- **Stale** metadata and **rental hard/soft/informational** severity are **not** duplicated on each preview row today; those behaviors are asserted at **evaluator / API / rental** layers.

## Duplication vs value

- Stale and integration policies appear in **unit** (evaluator/gate), **HTTP** (public API), **rental**, and **golden** tests. Each layer adds a different failure mode; trim only if two tests start asserting the **same** symptom without new signal.

## Product decisions worth keeping

Examples of tests that encode explicit product rules (keep when refactoring):

- Service-level write subscription **inactive** → **no** fallback to target default (`WriteCalendarResolverTest`).
- `manual_after_request` resources **excluded** from public slot union.
- Integration error **block** vs **warn** vs **stale warn_only** on public slots.
