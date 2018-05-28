<?php

namespace MyParcelCOM\Magento\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $tableName = $setup->getTable('sales_shipment_track');
        // Check if the table already exists
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            $setup->getConnection()->addColumn(
                $tableName,
                'myparcel_consignment_id',
                [
                    'type'      => Table::TYPE_TEXT,
                    'comment'   => 'MyParcel id',
                    'length'    => 255
                ]
            );
            $setup->getConnection()->addColumn(
                $tableName,
                'myparcel_status',
                [
                    'type'      => Table::TYPE_TEXT,
                    'comment' => 'MyParcel status',
                    'length'    => 255
                ]
            );
        }

        // Add status column to show in order grid
        $tableName = $setup->getTable('sales_order');
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            $setup->getConnection()->addColumn(
                $tableName,
                'track_status',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Status of MyParcel consignment'
                ]
            );
            $setup->getConnection()->addColumn(
                $tableName,
                'track_number',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Track number of MyParcel consignment'
                ]
            );
        }

        // Add status column to show in order grid
        $tableName = $setup->getTable('sales_order_grid');
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            $setup->getConnection()->addColumn(
                $tableName,
                'track_status',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Status of MyParcel consignment'
                ]
            );
            $setup->getConnection()->addColumn(
                $tableName,
                'track_number',
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Track number of MyParcel consignment'
                ]
            );

            /**
             * Add delivery options column for sales_order and quote (quote is for cart session)
             **/
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'delivery_options',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'MyParcel delivery options',
                ]
            );
        }

        $tableName = $setup->getTable('quote');
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            $setup->getConnection()->addColumn(
                $setup->getTable('quote'),
                'delivery_options',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'MyParcel delivery options',
                ]
            );
        }

        $setup->endSetup();
    }
}