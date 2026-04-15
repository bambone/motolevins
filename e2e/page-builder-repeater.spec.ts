/**
 * Browser regression for teleported Filament repeaters (Livewire DOM morph).
 *
 * Enable with:
 *   PLAYWRIGHT_E2E=1
 *   PLAYWRIGHT_BASE_URL=https://your-tenant-host.test
 *   PLAYWRIGHT_ADMIN_EMAIL=...
 *   PLAYWRIGHT_ADMIN_PASSWORD=...
 *   PLAYWRIGHT_PAGE_BUILDER_PATH=/admin/pages/1/edit
 *
 * Then: npx playwright install chromium && npm run test:e2e
 */
import { test, expect } from '@playwright/test';

const enabled =
    process.env.PLAYWRIGHT_E2E === '1' &&
    !!process.env.PLAYWRIGHT_BASE_URL &&
    !!process.env.PLAYWRIGHT_ADMIN_EMAIL &&
    !!process.env.PLAYWRIGHT_ADMIN_PASSWORD &&
    !!process.env.PLAYWRIGHT_PAGE_BUILDER_PATH;

test.describe('Page builder repeater (opt-in E2E)', () => {
    test.beforeEach(() => {
        test.skip(
            !enabled,
            'Set PLAYWRIGHT_E2E=1, PLAYWRIGHT_BASE_URL, PLAYWRIGHT_ADMIN_EMAIL, PLAYWRIGHT_ADMIN_PASSWORD, PLAYWRIGHT_PAGE_BUILDER_PATH',
        );
    });

    test.beforeEach(async ({ page }) => {
        await page.goto('/admin/login');
        await page.getByLabel(/email|почт/i).fill(process.env.PLAYWRIGHT_ADMIN_EMAIL!);
        await page.getByLabel(/password|парол/i).fill(process.env.PLAYWRIGHT_ADMIN_PASSWORD!);
        await page.locator('button[type="submit"]').first().click();
        await page.waitForURL(/\/admin/);
    });

    test('contacts channels: add then delete (DOM)', async ({ page }) => {
        await page.goto(process.env.PLAYWRIGHT_PAGE_BUILDER_PATH!);
        const addChannel = page.getByRole('button', { name: /добавить канал/i });
        await expect(addChannel).toBeVisible({ timeout: 30_000 });
        const before = await page.locator('li').filter({ has: page.locator('input') }).count();
        await addChannel.click();
        await expect
            .poll(async () => page.locator('li').filter({ has: page.locator('input') }).count())
            .toBeGreaterThan(before);
        const deleteButtons = page.getByRole('button', { name: /удалить/i });
        const delCount = await deleteButtons.count();
        await deleteButtons.nth(delCount - 1).click();
        const confirm = page.getByRole('button', { name: /подтвердить|удалить|ok|confirm/i });
        if (await confirm.count()) {
            await confirm.first().click();
        }
        await expect
            .poll(async () => page.locator('li').filter({ has: page.locator('input') }).count())
            .toBe(before);
    });

    test('data table: add row (DOM)', async ({ page }) => {
        await page.goto(process.env.PLAYWRIGHT_PAGE_BUILDER_PATH!);
        const addRow = page.getByRole('button', { name: /добавить строку/i });
        try {
            await expect(addRow).toBeVisible({ timeout: 15_000 });
        } catch {
            test.skip(true, 'Add a data_table block to this page or point PATH at a page that has one');
        }
        const markers = page.getByText(/строка таблицы/i);
        const before = await markers.count();
        await addRow.click();
        await expect.poll(async () => markers.count()).toBeGreaterThan(before);
    });
});
