<?php

namespace Fineweb\ProductPaymentOptions\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (!$installer->tableExists('payment_options')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('payment_options')
            )
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'method',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Payment Method'
                )
                ->addColumn(
                    'percentage_discount',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false, 'default' => '0.0000'],
                    'Percentage Discount'
                )
                ->addColumn(
                    'fixed_discount',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false, 'default' => '0.0000'],
                    'Fixed Discount'
                )
                ->addColumn(
                    'installments_without_interest',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Installments Without Interest'
                )
                ->addColumn(
                    'max_installments',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Max Installments'
                )
                ->addColumn(
                    'monthly_interest_rate',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false, 'default' => '0.0000'],
                    'Monthly Interest Rate'
                )
                ->addColumn(
                    'label',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Label'
                )
                ->setComment('Payment Options Table');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
