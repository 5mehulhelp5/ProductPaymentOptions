# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-10-23

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
  tests (`node --test Test/js`).

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

[1.0.0]: https://github.com/roni-clei-santos/ProductPaymentOptions/releases/tag/v1.0.0
