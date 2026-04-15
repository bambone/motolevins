# Playwright E2E (page builder)

Browser regression tests for teleported Filament repeaters. They are **skipped by default** unless all required env vars are set.

## Run locally

1. Install Chromium: `npx playwright install chromium`
2. Start the app on a **tenant host** that matches your data (Playwright does not configure tenancy for you).
3. Export variables (example):

```bash
export PLAYWRIGHT_E2E=1
export PLAYWRIGHT_BASE_URL=https://your-tenant-host.test
export PLAYWRIGHT_ADMIN_EMAIL=...
export PLAYWRIGHT_ADMIN_PASSWORD=...
export PLAYWRIGHT_PAGE_BUILDER_PATH=/admin/pages/1/edit
npm run test:e2e
```

Optional: start a dev server automatically if `PLAYWRIGHT_WEB_SERVER_COMMAND` is set (see `playwright.config.ts`). For multi-tenant setups you often still need the correct host in `PLAYWRIGHT_BASE_URL`.

## CI

Enable the `playwright-e2e` job by setting repository variable **`PLAYWRIGHT_E2E_ENABLED=true`** and configuring Actions secrets: `PLAYWRIGHT_BASE_URL`, `PLAYWRIGHT_ADMIN_EMAIL`, `PLAYWRIGHT_ADMIN_PASSWORD`, `PLAYWRIGHT_PAGE_BUILDER_PATH`. Deploy is **not** blocked on this job.

## Page builder: `data_table` theme partials

Repo check: the only tenant section view for the data table is `resources/views/tenant/themes/default/sections/data-table.blade.php`. There are no other theme overrides for `sections/data-table`.
