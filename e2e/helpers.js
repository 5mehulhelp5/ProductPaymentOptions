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

/**
 * Amounts the modal must show, per product price, for the two payment rows seeded in the store (see
 * magento-qa/seed-qa-data). Calculated by hand with exact decimals, half-up to cents, and kept fixed here:
 *
 *   Check / Money order: 10 % discount, up to 6 interest-free installments.
 *   Bank Transfer: R$ 50 discount, 3 interest-free installments, then Tabela Price at 1,99 % a.m. up to 12x,
 *     installment = price x i / (1 - (1 + i)^-n); minimum installment R$ 150, so smaller ones are hidden.
 *
 * Each installment is [number, amount, withInterest].
 */
const free = (...amounts) => amounts.map((amount, index) => [index + 1, amount, false]);
const priced = (first, ...amounts) => amounts.map((amount, index) => [first + index, amount, true]);

export const PLANS = {
  1234.56: {
    checkmo: { discount: 123.46, final: 1111.1, installments: free(1111.1, 555.55, 370.37, 277.78, 222.22, 185.18) },
    bank: {
      discount: 50, final: 1184.56,
      installments: [...free(1184.56, 592.28, 394.85), ...priced(4, 311.02, 251.24, 211.4, 182.96, 161.63)], // 9x = 145.06 < 150
    },
  },
  1100: {
    checkmo: { discount: 110, final: 990, installments: free(990, 495, 330, 247.5, 198, 165) },
    bank: { discount: 50, final: 1050, installments: [...free(1050, 525, 350), ...priced(4, 275.69, 222.7, 187.39, 162.18)] },
  },
  1500: {
    checkmo: { discount: 150, final: 1350, installments: free(1350, 675, 450, 337.5, 270, 225) },
    bank: {
      discount: 50, final: 1450,
      installments: [...free(1450, 725, 483.33), ...priced(4, 380.71, 307.54, 258.78, 223.96, 197.85, 177.56, 161.34)],
    },
  },
  2000.99: {
    checkmo: { discount: 200.1, final: 1800.89, installments: free(1800.89, 900.45, 600.3, 450.22, 360.18, 300.15) },
    bank: {
      discount: 50, final: 1950.99,
      installments: [
        ...free(1950.99, 975.5, 650.33),
        ...priced(4, 512.25, 413.8, 348.18, 301.34, 266.21, 238.91, 217.08, 199.23, 184.37),
      ],
    },
  },
  // Bundle with its default selection: every installment is under the R$ 150 minimum, so none is listed.
  61: {
    checkmo: { discount: 6.1, final: 54.9, installments: [] },
    bank: { discount: 50, final: 11, installments: [] },
  },
};

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
    lines,
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
