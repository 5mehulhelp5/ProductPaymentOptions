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

The expected values are fixed in `helpers.js` (`PLANS`), calculated by hand for the configuration the store was seeded with:
Check / Money order with 10 % off and 6 interest-free installments; Bank Transfer with R$ 50 off, 3 interest-free
installments and the Tabela Price at 1,99 % per month up to 12x; a R$ 150 minimum installment. Products: a simple one at
R$ 1.234,56, a configurable one with sizes at R$ 1.100,00 / 1.234,56 / 1.500,00 / 2.000,99 and a dynamic-price bundle.
