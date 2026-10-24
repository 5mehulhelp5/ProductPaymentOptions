# End-to-end tests

Playwright tests that run against a real Magento store with the module installed and configured (see the
`Screenshots` and `Development` sections of the main README). They are not part of the CI workflow: they need a store.

```bash
npm i -D @playwright/test           # or use an existing install; browsers: `npx playwright install chromium`,
                                    # or point QA_BROWSER at a Chromium-based browser you already have
QA_BASE_URL=http://localhost QA_ADMIN_USER=... QA_ADMIN_PASSWORD=... npx playwright test --config e2e/playwright.config.js
```

Environment variables: `QA_BASE_URL` (default `http://localhost`), `QA_BROWSER` (browser executable),
`QA_PHASE` (`before` / `after`, only names the screenshot folder), `QA_ADMIN_USER` / `QA_ADMIN_PASSWORD`.

The expected values are computed inside the tests, in cents, from the configuration the store was seeded with
(10 % discount and 6 interest-free installments for Check / Money order; R$ 50 discount, 3 interest-free and
compound 1.99 % per month up to 12 installments for Bank Transfer; R$ 150 minimum installment; simple product at
R$ 1.234,56; configurable sizes at R$ 1.100,00 / 1.234,56 / 1.500,00 / 2.000,99).
