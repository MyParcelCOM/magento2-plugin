<?php

namespace MyParcelCOM\Magento\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $tableName = $setup->getTable('myparcelcom_data');
        if (!$setup->getConnection()->isTableExists($tableName)) {
            $table = $setup->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                    'track_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                    'shipment_id',
                    Table::TYPE_TEXT,
                    36
                )
                ->addColumn(
                    'status_code',
                    Table::TYPE_TEXT,
                    255
                )
                ->addColumn(
                    'status_name',
                    Table::TYPE_TEXT,
                    255
                )
                ->addColumn(
                    'tracking_code',
                    Table::TYPE_TEXT,
                    255
                )
                ->addColumn(
                    'tracking_url',
                    Table::TYPE_TEXT,
                    255
                )
                ->setComment('MyParcel.com Data Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');

            $setup->getConnection()->createTable($table);

            $setup->getConnection()->addIndex(
                $setup->getTable('myparcelcom_data'),
                $setup->getIdxName('myparcelcom_data', ['order_id']),
                ['order_id']
            );

            $setup->getConnection()->addIndex(
                $setup->getTable('myparcelcom_data'),
                $setup->getIdxName('myparcelcom_data', ['track_id']),
                ['track_id']
            );

            $setup->getConnection()->addIndex(
                $setup->getTable('myparcelcom_data'),
                $setup->getIdxName('myparcelcom_data', ['shipment_id']),
                ['shipment_id']
            );
        }

        $setup->endSetup();
    }
}
