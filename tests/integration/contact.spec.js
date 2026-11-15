import { test, expect } from '@playwright/test';

test.describe('ksf_FA_Contact Contact Module', () => {
  test('should load contact list page', async ({ page }) => {
    await page.goto('/modules/ksf_FA_Contact/pages/contacts.php');
    await expect(page.locator('h1, h2')).toContainText(/Contact/i);
  });
});
