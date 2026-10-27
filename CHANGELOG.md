# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Development tooling only; nothing changes in the installed module.

### Changed

- CI only: the workflow uses `actions/checkout@v5` and `actions/setup-node@v5`, which run on Node 24, instead of the v4 versions GitHub flags as Node 20 (deprecated).

### Fixed

- The git hooks no longer freeze when Docker Desktop is starting or stuck. `dev/ci/in-container.sh` checks the container with a
  deadline (`QA_DOCKER_TIMEOUT`, 5 seconds by default) and, when Docker does not answer, skips the PHP checks with a warning, as
  it already did when the container is stopped.

## [2.0.0] - 2026-10-24

Quality release: no change in what the storefront or the admin show (the end-to-end tests pass with the same hand-calculated
amounts), but the module is now structured, tested and checked the way a Magento 2 module should be.

### Breaking changes

This release changes PHP classes and the markup of the storefront. Nothing the shopper sees changes, but code and themes that
touch the module have to follow.

**PHP**

- `Fineweb\ProductPaymentOptions\Block\ProductPaymentOptions` was **removed** and replaced by the ViewModel
  `Fineweb\ProductPaymentOptions\ViewModel\PaymentOptions`. The layout block is now a plain
  `Magento\Framework\View\Element\Template` that receives the ViewModel as the `view_model` argument; a template or a plugin that
  used `$block->getProductPaymentOptions()` must use `$block->getData('view_model')`.

  | 1.0.0 (block) | 2.0.0 (ViewModel) |
  |---|---|
  | `getProductPaymentOptions(): ?array` | `getOptions(): ?array` |
  | `getMinInstallmentValue(): ?float` | `getMinInstallmentValue(): float` (`0.0` when not configured) |
  | `serializeForScript(array): string` | `getInitConfig(): string` (builds the whole `x-magento-init` payload) |
  | `XML_PATH_PAYMENTS`, `XML_PATH_MIN_INSTALLMENT_VALUE` | the same constants on `Model\Config` |

- New `Fineweb\ProductPaymentOptions\Model\Config` reads the settings (always with a scope).
  The constructor of `Model\Config\Source\PaymentMethods` takes it instead of `ScopeConfigInterface`; anything that
  instantiates the class by hand or overrides its constructor arguments in `di.xml` must be updated.

**Storefront markup and scripts**

- Fixed ids and generic classes were replaced by `data-*` hooks and BEM classes:
  `#openPaymentOptionsModal` → `[data-action="open-payment-options"]`, `#paymentOptionsModal` → `[data-role="payment-options-modal"]`,
  `.fineweb-payment-options-modal` → `.fineweb-payment-options__modal`, `.payment-option-title` / `.payment-option-content` →
  `.fineweb-payment-options__title` / `__note`, `.fineweb-installments` → `.fineweb-payment-options__plan` and its lines
  `.fineweb-discount-amount` / `.fineweb-final-amount` → `__discount` / `__final`. The `x-magento-init` selector is now
  `[data-role="fineweb-payment-options"]`. A theme that styled or scripted the old hooks must be updated.
- The modal is built with elements, no longer with HTML strings, and the inline `style` of the modal container is gone.

**Translations**

- The pieces `"Discount: "`, `"Final Price: "`, `"with "` and `"interest per month"` were replaced by whole phrases with placeholders:
  `Discount: %1`, `Final Price: %1`, `%1 x %2` and `%1 x %2 (with %3% interest per month)`. Translation overrides must use the new keys.

### Changed

- The block that fed the template is a **ViewModel** (`ArgumentInterface`) passed as a layout argument; the configuration
  is read by `Model/Config`, and every read passes its scope.
- The plan in the modal is built with elements and `.text()` instead of concatenated HTML, and every sentence is one
  translated phrase with placeholders (`%1 x %2 (with %3% interest per month)`) instead of glued pieces. The CSV files
  carry exactly those phrases.
- The markup uses `data-role` / `data-action` hooks scoped to the widget element instead of fixed ids, and no inline style.
- CSS classes are BEM in kebab-case with the `fineweb-` prefix (`fineweb-payment-options__button`); the LESS has at most 3
  levels and is mobile first.

### Added

- CI jobs: blacklist, `composer validate --strict`, tests exist, multi-store scope, phpcs with warnings blocking, phpmd (Magento
  rule set), phpstan level 9, PHPUnit with a 70% per-file coverage gate on PHP 8.2 and 8.3, ESLint 10 and the JavaScript tests.
- Local git hooks (`.githooks`, `setup-dev.sh`), the review guide (`.github/copilot-instructions.md`), a pull request
  template and `CLAUDE.md`.
- Unit tests for the new classes and for the admin field array and its dropdown column (39 tests, 100% coverage of every file).

### Fixed

- The widget required the whole `jquery/ui`, which makes Magento activate its jQuery UI compatibility layer on the product page
  ("Fallback to JQueryUI Compat activated"); it now requires only `jquery-ui-modules/widget`.
- The Composer package no longer carries the tests, the CI files and the tooling (`export-ignore`).

## [1.0.0] - 2026-10-24

First tagged release. It modernizes the module that had been running since 2024 for Magento 2.4.7+ and PHP 8.2/8.3.

### Added

- PHPUnit unit tests for the block, the payment method source and the admin field array.
- GitHub Actions workflow running phpcs (Magento2 coding standard), phpstan (level 9) and PHPUnit on PHP 8.2 and 8.3.
- ACL resource for the configuration section and an explicit default for the enable switch.
- MIT license, `.gitignore` and this changelog.

### Changed

- Interest-bearing installments use the fixed-installment table (**Tabela Price**), `price × i / (1 - (1 + i)^-n)`,
  instead of `price × (1 + i)^n / n`. R$ 1.184,56 at 1,99 % per month in 4x is now R$ 311,02 (it was R$ 320,43) and
  in 9x R$ 145,06 (it was R$ 157,16, so it is now hidden by a R$ 150 minimum installment). Amounts are rounded only when
  displayed, and the monthly rate is written with the store's decimal separator (`1,99%`, not `1.99%`).
- The calculation lives in `view/frontend/web/js/installments.js`, a module without dependencies that is covered by
  tests (`node --test Test/js/installments.test.mjs`).

- Requires PHP `~8.2.0||~8.3.0` and Magento framework `^103.0.7` (Magento 2.4.7+). All Magento modules the code
  depends on are declared in `composer.json`, and PSR-4 autoloading is configured.
- Replaced the deprecated `Magento\Framework\Option\ArrayInterface` with `OptionSourceInterface`; added strict types
  and constructor property promotion.
- Removed the deprecated `setup_version` from `module.xml` and declared the module sequence.
- The installment and discount calculation is rounded to cents (it used one decimal place) and prices are formatted with
  the store's price format instead of the browser locale.
- The price is observed through the price box `reloadPrice` event instead of mutation observers on the whole document.
- Rows of payment methods that are not active in the current store are no longer shown.

### Fixed

- Installed as a Composer package, every page failed with `Class "...\Block\ProductPaymentOptions" not found`:
  `composer.json` had no PSR-4 autoload. Its PHP requirement also stopped at 8.2, so Composer refused PHP 8.3.
- Prices of 1,000 or more were read as a few units in pt-BR stores (`R$ 1.234,56` was parsed as `1.234`), and amounts
  were shown in the browser's number format (`R$1.10`) instead of the store's (`R$ 1,10`).
- The saved payment method was never pre-selected in the admin dropdown (the row was read with the wrong key).
- Configuration was read in the default scope although the fields are available per website and store view: with the
  options saved for a website, the product page showed nothing.
- A payment method enabled only for a website showed its raw code instead of its title, and could not be chosen in the
  admin. The admin now lists the methods active in the scope being edited, and keeps already saved methods selectable.
- An empty "Minimum Installment Value" raised a `TypeError`; malformed JSON raised a PHP warning.
- The payment methods were instantiated once per configured row.
- A saved row without "Payment Method Content to Show" could trigger undefined index notices.

### Removed

- The packaged `.zip` archive (and the `.DS_Store` it bundled) that was committed to the repository.

[2.0.0]: https://github.com/roni-clei-santos/ProductPaymentOptions/releases/tag/v2.0.0
[1.0.0]: https://github.com/roni-clei-santos/ProductPaymentOptions/releases/tag/v1.0.0
