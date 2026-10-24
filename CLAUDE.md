# CLAUDE.md — Fineweb_ProductPaymentOptions

Magento 2 / Adobe Commerce module (`fineweb/product-payment-options-extension`, MIT). On the product page it shows, per
payment method, the discount, the final price and the installment plan (interest-free installments, then the
fixed-installment table, **Tabela Price**, at a monthly rate; installments below a minimum value are hidden).
Declared for Magento 2.4.7+ (`magento/framework ^103.0.7`) and PHP 8.2 – 8.3.

## Layout

| Path | What |
|---|---|
| `Model/Config.php` | Reads the configuration; **always passes the scope** (fields are per website / store view) |
| `ViewModel/PaymentOptions.php` | Data for the template: rows, minimum installment, `x-magento-init` JSON |
| `Block/Adminhtml/Form/Field/{Rows,Column}.php` | Admin field array (Magento requires blocks here) |
| `Model/Config/Source/PaymentMethods.php` | Admin dropdown: payment methods active in the **edited scope** |
| `view/frontend/templates/product/payment_options.phtml` | Markup only; `data-role` / `data-action` hooks, BEM classes |
| `view/frontend/web/js/installments.js` | Pure calculation (no dependencies, covered by `Test/js`) |
| `view/frontend/web/js/product-options-widget.js` | Widget: renders the plan with `.text()`, `$t()` phrases with placeholders |
| `i18n/{en_US,pt_BR}.csv` | Exact phrases; UTF-8 without BOM; final newline |
| `Test/Unit` | PHPUnit, mirrors the structure (`Foo/Bar.php` → `Test/Unit/Foo/BarTest.php`) |
| `Test/js` | `node --test` tests of the calculation, expected values calculated by hand |
| `e2e/` | Playwright tests against a running store, expected values fixed by hand |
| `dev/ci`, `dev/phpmd` | CI checks (PHP scripts) and the vendored Magento PHPMD rule set |

## Commands

```bash
./setup-dev.sh                # composer install, npm install, enables the git hooks (core.hooksPath = .githooks)
composer check                # blacklist, phpcs, phpmd, phpstan, tests-exist, multi-store, coverage
composer test                 # PHPUnit only
composer coverage             # PHPUnit with xdebug + the 70% per-file gate (prints the percentage of every file)
composer phpcs | phpmd | phpstan | blacklist | check-tests-exist | check-scope
npm run lint                  # ESLint 10 (flat config) on view/**/*.js, zero warnings allowed
npm run test:js               # node --test Test/js/installments.test.mjs
```

The dependencies come from a public mirror of the original Magento packages, so no `repo.magento.com` keys are needed:
`composer config -g repositories.magento-mirror composer https://mirror.mage-os.org/`.

## Quality rules (enforced in CI and by the git hooks)

- Blacklist: no `var_dump(`, `print_r(`, `console.log(`, `die`, `exit`, `eval(`, `phpinfo`, `ObjectManager::getInstance`,
  `DebuggerUtility`, `localhost`, `$_GET[`/`$_POST[`/`$_REQUEST[`, merge markers in php/js/phtml/html (not in `Test/`, `e2e/`).
- phpcs Magento2 on php + phtml (+ less) with **warnings blocking** (`warning-severity` 1). Two documented exclusions:
  the BEM `__` class name sniff, and `dev/ci` + `dev/phpmd` (tooling / vendored code).
- phpmd with the Magento rule set (`dev/phpmd/ruleset.xml`), phpstan level 9, no baseline.
- PHPUnit with coverage of at least 70% **per file**; every production PHP file (except `registration.php`, `Api/`,
  interfaces) has its `Test/Unit` counterpart; every `scopeConfig->getValue()/isSetFlag()` passes a scope.
- JavaScript: whole sentences with placeholders in `$t()`, no concatenated HTML (`.text()` or templates), `data-*` hooks scoped
  to the widget element, no fixed ids, no inline styles; CSS in BEM kebab-case with the `fineweb-` prefix, at most 3 LESS
  nesting levels, mobile first, single quotes.
- Business logic goes in services behind `Api/` interfaces; templates get data from ViewModels, never from logic in a block.
- `composer validate --strict` passes. Not enforced: `phpcpd` — its last release (6.0.3) needs `sebastian/cli-parser ^1`, which
  conflicts with PHPUnit 10.

## Behavior to preserve

Do not change what the storefront shows: with the test store (simple product R$ 1.234,56, Check / Money order 10 % off and
6 interest-free installments, Bank Transfer R$ 50 off with 3 interest-free installments and Tabela Price at 1,99 % a.m., minimum
installment R$ 150) the modal shows Bank Transfer 4x R$ 311,02 … 8x R$ 161,63 and no 9x. The end-to-end tests must keep passing.

## End-to-end tests

They need a Magento store with the module installed (see `e2e/README.md`). `QA_BROWSER` can point to a Chromium-based
browser you already have, so no browser download is needed.
