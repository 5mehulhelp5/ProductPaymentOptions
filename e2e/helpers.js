import { expect } from '@playwright/test';

/** Folder for screenshots: test-results/screenshots/<phase>/ ; phase is "before" or "after" the fixes. */
export const PHASE = process.env.QA_PHASE ?? 'after';
/** Screenshot of the page, or of one element when `target` is given (so the result is not left below the fold). */
export const shot = async (page, name, target = page) => {
  await page.waitForTimeout(700); // let slide / fade animations finish, so the element is captured at its final size
  return target.screenshot({ path: `test-results/screenshots/${PHASE}/${name}.png` });
};

/** "R$ 1.234,56" -> 1234.56 (pt_BR price format). */
export const parseBRL = (text) => parseFloat(text.replace(/[^\d,]/g, '').replace(',', '.'));

/** The price format of the pt_BR store: "R$ 1.234,56". */
export const BRL_FORMAT = /^R\$\s?\d{1,3}(\.\d{3})*,\d{2}$/;

/** Round to cents by going through a decimal string, independent of the module's own rounding code. */
export const cents = (value) => Number(value.toFixed(2));

/**
 * Hand calculation of one payment row, from the configuration seeded in the store:
 * a fixed discount wins over a percentage one; interest is compound; installments below `min` are hidden.
 */
export function expectedRow(price, { fixed = 0, percentage = 0, max, noInterest, rate = 0 }, min) {
  let final = price;
  if (fixed > 0) final = price - fixed;
  else if (percentage > 0) final = price - (price * percentage) / 100;
  final = cents(Math.max(final, 0));

  const installments = [];
  for (let n = 1; n <= max; n += 1) {
    const amount = n <= noInterest ? final / n : (final * (1 + rate / 100) ** n) / n;
    if (cents(amount) >= min) installments.push({ n, amount: cents(amount), withInterest: n > noInterest });
  }
  return { final, discount: cents(price - final), hasDiscount: fixed > 0 || percentage > 0, installments };
}

/** Rows seeded in the store (website scope), see magento-qa/seed-qa-data. */
export const CHECKMO = { fixed: 0, percentage: 10, max: 6, noInterest: 6, rate: 0 };
export const BANK_TRANSFER = { fixed: 50, percentage: 0, max: 12, noInterest: 3, rate: 1.99 };
export const MIN_INSTALLMENT = 150;

/** Open the product page and the payment options modal, and wait for the calculation to be rendered. */
export async function openModal(page) {
  // The button is server-rendered: wait for the widget to have calculated (and bound its click handler) first.
  await page.locator('#paymentOptionsModal .fineweb-final-amount').first().waitFor({ state: 'attached' });
  await page.locator('#openPaymentOptionsModal').click();
  await expect(page.locator('#paymentOptionsModal .fineweb-final-amount').first()).toBeVisible();
}

/** Read what the modal shows for one payment method. */
export async function readRow(page, title) {
  const row = page.locator('#paymentOptionsModal li', { has: page.locator('.payment-option-title', { hasText: title }) });
  const text = async (selector) => (await row.locator(selector).count() ? row.locator(selector).first().innerText() : null);
  const lines = await row.locator('.fineweb-installments p').allInnerTexts();
  const installments = lines
    .map((line) => line.match(/^(\d+) x (R\$\s?[\d.,]+)(.*)$/))
    .filter(Boolean)
    .map(([, n, amount, rest]) => ({ n: Number(n), amount: parseBRL(amount), withInterest: rest.trim().startsWith('(') }));
  const discount = await text('.fineweb-discount-amount');
  const final = await text('.fineweb-final-amount');
  return {
    discount: discount ? parseBRL(discount) : null,
    final: final ? parseBRL(final) : null,
    finalText: final,
    installments,
  };
}

/** The current product price shown on the page, as a number. */
export async function shownPrice(page) {
  // Bundles show "from/to" in the main box; their final price lives in the "Your Customization" summary box.
  const main = page.locator('.product-info-main [data-price-type="finalPrice"] .price');
  const price = (await main.count()) ? main.first() : page.locator('[data-price-type="finalPrice"] .price').first();
  return parseBRL(await price.innerText());
}

export function collectPageErrors(page) {
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  return errors;
}
