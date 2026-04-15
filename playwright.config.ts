import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';
const webServerCommand = process.env.PLAYWRIGHT_WEB_SERVER_COMMAND?.trim();

export default defineConfig({
    testDir: 'e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    ...(webServerCommand
        ? {
              webServer: {
                  command: webServerCommand,
                  url: baseURL,
                  reuseExistingServer: !process.env.CI,
              },
          }
        : {}),
    use: {
        baseURL,
        trace: 'on-first-retry',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
