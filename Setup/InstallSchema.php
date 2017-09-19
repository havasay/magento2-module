<?php
 
namespace Havasay\Havasay\Setup;
 
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
 
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
 
        // Get tutorial_simplenews table
        $tableName = $installer->getTable('havasay_organization_details');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            // Create tutorial_simplenews table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'ID'
                )
                ->addColumn(
                    'org_key',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'OrgKey'
                )
                ->addColumn(
                    'org_id',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'OrgId'
                )
                ->addColumn(
                    'channel_id',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'ChannelId'
                )
                ->addColumn(
                    'org_secret',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'OrgSecret'
                )
                ->addColumn(
                    'org_name',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'OrgName'
                )
                ->addColumn(
                    'store_id',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'StoreId'
                )
                ->addColumn(
                    'havasay_path',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'HavasayPath'
                )
                ->addColumn(
                    'list_name',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'ListName'
                )
                ->setComment('Organization Table');
            $installer->getConnection()->createTable($table);
        }
        
        // Get tutorial_simplenews table
        $tableName1 = $installer->getTable('havasay_cron_status');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName1) != true) {
            // Create tutorial_simplenews table
            $table = $installer->getConnection()
                    ->newTable($tableName1)
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                            ],
                        'ID'
                    )
                    ->addColumn(
                        'store_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['nullable' => false, 'default' => 0],
                        'storeId'
                    )
                    ->addColumn(
                        'job_name',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'JobName'
                    )
                    ->addColumn(
                        'entity_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['nullable' => false, 'default' => 0],
                        'EntityId'
                    )
                    ->addColumn(
                        'timestamp',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => false],
                        'Timestamp'
                    )
                    ->addColumn(
                        'executed_at',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => false],
                        'ExecutedAt'
                    )
                    ->setComment('Havasay cron status table')
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }
        /////////////////////////// entity id table for failed objects
        // Get tutorial_simplenews table
        $tableName2 = $installer->getTable('havasay_cron_entity_status');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName2) != true) {
            // Create tutorial_simplenews table
            $table = $installer->getConnection()
                    ->newTable($tableName2)
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                            ],
                        'ID'
                    )//2.storeId, cronjobName, failed EntityId, status
                    ->addColumn(
                        'store_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['nullable' => false, 'default' => 0],
                        'StoreId'
                    )
                    ->addColumn(
                        'job_name',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'JobName'
                    )
                    ->addColumn(
                        'entity_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['nullable' => false, 'default' => 0],
                        'EntityId'
                    )
                    ->addColumn(
                        'failed',
                        Table::TYPE_SMALLINT,
                        null,
                        ['nullable' => false, 'default' => '0'],
                        'Status'
                    )
                    ->addColumn(
                        'additional_data',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'AdditionalData'
                    )
                    ->addColumn(
                        'error_message',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'ErrorMessage'
                    )
                    ->setComment('Havasay Cron Status Table')
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }
 
        $installer->endSetup();
    }
}
