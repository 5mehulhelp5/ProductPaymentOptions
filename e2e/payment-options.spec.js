import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

import { expect, test } from '@playwright/test';

import { BRL_FORMAT, PLANS, collectPageErrors, openModal, readRow, shot, shownPrice } from './helpers.js';

const CHECKMO_TITLE = 'Check / Money order';
const BANK_TITLE = 'Bank Transfer Payment';
const modal = (page) => page.locator('.modal-popup._show .modal-inner-wrap');

/** Compare what the modal shows for one payment method with the hand-calculated plan of that price. */
async function assertRow(page, title, method, price) {
  const expected = PLANS[price]?.[method];
  expect(expected, `no hand-calculated plan for price ${price}`).toBeDefined();
  const shown = await readRow(page, title);

  expect(shown.final, `${title}: final price (page shows "${shown.finalText}")`).toBe(expected.final);
  expect(shown.discount, `${title}: discount`).toBe(expected.discount);
  expect(
    shown.installments.map(({ n, amount, withInterest }) => [n, amount, withInterest]),
    `${title}: installments of R$ ${price}`,
  ).toEqual(expected.installments);
}

test.describe('simple product priced R$ 1.234,56', () => {
  const URL = '/joust-duffle-bag.html';

  test('shows the button and opens the modal with both payment methods', async ({ page }) => {
    const errors = collectPageErrors(page);
    await page.goto(URL);

    await expect(page.locator('[data-action="open-payment-options"]')).toBeVisible();
    await shot(page, 'ppo-01-product-page-button');
    await openModal(page);

    await expect(page.locator('[data-role="payment-option"] .fineweb-payment-options__title')).toHaveText([CHECKMO_TITLE, BANK_TITLE]);
    await expect(page.locator('[data-role="payment-option"] .fineweb-payment-options__note')).toHaveText(['10% cash discount', 'R$ 50 off, up to 12x']);
    await shot(page, 'ppo-02-modal', modal(page));
    expect(errors).toEqual([]);
  });

  test('the price is read as 1234.56, not 1.234 (thousands separator bug)', async ({ page }) => {
    await page.goto(URL);
    expect(await shownPrice(page)).toBe(1234.56);
    await openModal(page);

    const checkmo = await readRow(page, CHECKMO_TITLE);
    expect(checkmo.final).toBe(1111.1); // 1234.56 - 10%
    expect(checkmo.discount).toBe(123.46);
  });

  test('percentage discount and final price match the hand calculation', async ({ page }) => {
    await page.goto(URL);
    await openModal(page);
    await assertRow(page, CHECKMO_TITLE, 'checkmo', 1234.56);
  });

  test('fixed discount, interest-free installments and Tabela Price installments match the hand calculation', async ({ page }) => {
    await page.goto(URL);
    await openModal(page);
    await assertRow(page, BANK_TITLE, 'bank', 1234.56);

    const bank = await readRow(page, BANK_TITLE);
    expect(bank.final).toBe(1184.56);
    expect(bank.installments.filter((i) => !i.withInterest).map((i) => i.n)).toEqual([1, 2, 3]);
    expect(bank.installments.filter((i) => i.withInterest).map((i) => i.n)).toEqual([4, 5, 6, 7, 8]);
    expect(bank.installments.find((i) => i.n === 4).amount).toBe(311.02); // not 320.43 (price x (1 + i)^n / n)
    await shot(page, 'ppo-03-installments', page.locator('[data-role="payment-option"]').nth(1));
  });

  test('installments below the minimum installment value are hidden: 9x of R$ 145,06 is under R$ 150', async ({ page }) => {
    await page.goto(URL);
    await openModal(page);

    const bank = await readRow(page, BANK_TITLE);
    expect(bank.installments.map((i) => i.n)).not.toContain(9);
    expect(bank.installments.map((i) => i.n)).not.toContain(12);
    expect(Math.max(...bank.installments.map((i) => i.n))).toBe(8);
    for (const { n, amount } of bank.installments) expect(amount, `${n}x`).toBeGreaterThanOrEqual(150);
  });

  test('the monthly interest is written with the store decimal separator: 1,99% and not 1.99%', async ({ page }) => {
    await page.goto(URL);
    await openModal(page);

    const lines = (await readRow(page, BANK_TITLE)).lines.filter((line) => line.includes('%'));
    expect(lines.length).toBe(5); // 4x to 8x
    for (const line of lines) {
      expect(line).toContain('1,99%');
      expect(line).not.toContain('1.99%');
    }
  });

  test('amounts use the store price format even though the browser locale is en-US', async ({ page }) => {
    await page.goto(URL);
    await openModal(page);

    const amounts = await page.locator('[data-role="payment-options-modal"] .fineweb-payment-options__final').allInnerTexts();
    for (const text of amounts) expect(text.replace(/^[^R]*/, '')).toMatch(BRL_FORMAT);
  });
});

test.describe('configurable product, price follows the chosen size', () => {
  const URL = '/apollo-running-short.html';
  const PRICES = { 32: 1100, 33: 1234.56, 34: 1500, 36: 2000.99 };

  test('recalculates for each size', async ({ page }) => {
    const errors = collectPageErrors(page);
    await page.goto(URL);
    await page.locator('.swatch-attribute.color .swatch-option').first().click();

    for (const [size, price] of Object.entries(PRICES)) {
      await page.locator(`.swatch-attribute.size .swatch-option:text-is("${size}")`).click();
      await expect.poll(() => shownPrice(page), { message: `price for size ${size}` }).toBe(price);

      await openModal(page);
      await assertRow(page, CHECKMO_TITLE, 'checkmo', price);
      await assertRow(page, BANK_TITLE, 'bank', price);
      if (size === '33') await shot(page, 'ppo-04-configurable-size-33', modal(page));
      if (size === '36') await shot(page, 'ppo-05-configurable-size-36', modal(page));
      await page.locator('.modal-popup._show .action-close').click();
    }
    expect(errors).toEqual([]);
  });
});

test.describe('bundle product (dynamic price)', () => {
  test('opens, shows no script errors and matches the displayed price once options are chosen', async ({ page }) => {
    const errors = collectPageErrors(page);
    await page.goto('/sprite-yoga-companion-kit.html');
    await expect(page.locator('[data-action="open-payment-options"]')).toBeVisible();

    for (const group of await page.locator('.bundle-options-container .field.option.required').all()) {
      const radio = group.locator('input[type="radio"]').first();
      if (await radio.count()) await radio.check();
    }
    await expect.poll(() => shownPrice(page).catch(() => NaN), { timeout: 30_000 }).toBeGreaterThan(0);
    const price = await shownPrice(page);

    await openModal(page);
    await assertRow(page, CHECKMO_TITLE, 'checkmo', price);
    await assertRow(page, BANK_TITLE, 'bank', price);
    await shot(page, 'ppo-06-bundle', modal(page));
    expect(errors).toEqual([]);
  });
});

test.describe('admin', () => {
  test('the saved payment method is pre-selected in each row (website scope)', async ({ page }) => {
    const user = process.env.QA_ADMIN_USER;
    const password = process.env.QA_ADMIN_PASSWORD;
    test.skip(!user || !password, 'set QA_ADMIN_USER and QA_ADMIN_PASSWORD');

    await page.setViewportSize({ width: 1800, height: 1100 });
    await page.goto('/admin');
    await page.locator('#username').fill(user);
    await page.locator('#login').fill(password);
    await page.locator('.action-login').click();
    await expect(page.locator('.page-title, .dashboard-container').first()).toBeVisible();

    // Reuse the secret key of the config controller for the website-scoped section URL.
    const configLink = await page.locator('a[href*="/system_config/edit/"]').first().getAttribute('href');
    const key = configLink.match(/\/key\/([0-9a-f]+)/)[1];
    await page.goto(`/admin/admin/system_config/edit/section/product_payment_options/website/1/key/${key}/`);

    const rows = page.locator('#product_payment_options_payment_discount_payments tbody tr');
    await expect(rows).toHaveCount(2);
    const selected = await rows.locator('select[name$="[payment_method]"]').evaluateAll((selects) => selects.map((s) => s.value));
    expect(selected).toEqual(['checkmo', 'banktransfer']);
    await shot(page, 'ppo-07-admin-configuration', page.locator('#product_payment_options_payment_discount'));
  });
});

const PRODUCT_PAGES = {
  simple: '/joust-duffle-bag.html',
  configurable: '/apollo-running-short.html',
  bundle: '/sprite-yoga-companion-kit.html',
};

test.describe('JavaScript', () => {
  for (const [type, url] of Object.entries(PRODUCT_PAGES)) {
    test(`no JavaScript error on the ${type} product page`, async ({ page }) => {
      const errors = collectPageErrors(page);
      await page.goto(url, { waitUntil: 'networkidle' });
      await page.waitForFunction(() => window.require?.defined?.('Fineweb_ProductPaymentOptions/js/product-options-widget'));
      await openModal(page);

      expect(errors).toEqual([]);
    });
  }

  test('the browser gets the JavaScript of this checkout, not a cached or stale copy', async ({ page }) => {
    const files = ['product-options-widget.js', 'installments.js'];
    const responses = files.map((file) => page.waitForResponse((response) => response.url().endsWith(`/Fineweb_ProductPaymentOptions/js/${file}`)));

    await page.goto(PRODUCT_PAGES.simple, { waitUntil: 'networkidle' });

    for (const [index, file] of files.entries()) {
      const served = await (await responses[index]).text();
      expect(served, file).toBe(readFileSync(resolve(process.cwd(), `view/frontend/web/js/${file}`), 'utf8'));
    }
  });
});
