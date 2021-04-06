<?php

namespace MyParcelCOM\Magento\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $tableName = $setup->getTable('sales_shipment_track');
        if ($setup->getConnection()->isTableExists($tableName) === true) {
            $setup->getConnection()->addColumn(
                $tableName,
                'myparcel_consignment_id',
                [
                    'type'    => Table::TYPE_TEXT,
                    'comment' => 'MyParcel id',
                    'length'  => 255,
                ]
            );
            $setup->getConnection()->addColumn(
                $tableName,
                'myparcel_status',
                [
                    'type'    => Table::TYPE_TEXT,
                    'comment' => 'MyParcel status',
                    'length'  => 255,
                ]
            );
        }

        $tableName = $setup->getTable('sales_order');
        if ($setup->getConnection()->isTableExists($tableName) === true) {
            $setup->getConnection()->addColumn(
                $tableName,
                'track_status',
                [
                    'type'    => Table::TYPE_TEXT,
                    'comment' => 'Status of MyParcel consignment',
                ]
            );
            $setup->getConnection()->addColumn(
                $tableName,
                'track_number',
                [
                    'type'    => Table::TYPE_TEXT,
                    'comment' => 'Track number of MyParcel consignment',
                ]
            );
        }

        $tableName = $setup->getTable('sales_order_grid');
        if ($setup->getConnection()->isTableExists($tableName) === true) {
            $setup->getConnection()->addColumn(
                $tableName,
                'track_status',
                [
                    'type'    => Table::TYPE_TEXT,
                    'comment' => 'Status of MyParcel consignment',
                ]
            );
            $setup->getConnection()->addColumn(
                $tableName,
                'track_number',
                [
                    'type'    => Table::TYPE_TEXT,
                    'comment' => 'Track number of MyParcel consignment',
                ]
            );
        }

        $setup->endSetup();
    }
}
