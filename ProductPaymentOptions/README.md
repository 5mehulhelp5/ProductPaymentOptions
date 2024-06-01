# Fineweb Product Payment Options

The Fineweb Product Payment Options module enhances Magento 2 stores by providing additional payment options for products.

## Installation

To install this module, follow these steps:

1. Copy the module files to the `app/code/Fineweb/ProductPaymentOptions` directory of your Magento 2 installation.
2. Run the following commands in your Magento 2 root directory:

   ```bash
   php bin/magento module:enable Fineweb_ProductPaymentOptions
   php bin/magento setup:upgrade
   ```

3. Verify that the module is enabled by running:

   ```bash
   php bin/magento module:status
   ```

## Features

- Adds extra payment options to product pages.
- Allows customization of payment methods and discounts through the admin panel.
- Provides an intuitive interface for managing payment options.

## Configuration

Once installed, you can configure the payment options by navigating to `Stores > Configuration > Fineweb > Product Payment Options` in the admin panel.

### Functions

This module provides the following functions:

- **Enhanced Payment Options**: Display additional payment methods on product pages to offer customers more choices during checkout.
- **Customization**: Customize payment methods, discounts, and other settings through the Magento admin panel.
- **Intuitive Interface**: User-friendly interface for managing and configuring payment options without extensive technical knowledge.

## Usage

After configuring the payment options, they will be displayed on the product pages of your store. Customers can view and select the available payment methods during checkout.

## Compatibility

- This module is compatible with Magento 2.x versions.
- This module is NOT compatible with Grouped Products.

## Support

For any issues or inquiries, please contact our support team at support@fineweb.com.
