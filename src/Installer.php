<?php

namespace AdvancedObjectSearchBundle;

use AdvancedObjectSearchBundle\Model\SavedSearch;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Pimcore\Model\User\Permission\Definition;

class Installer extends MigrationInstaller
{
    const QUEUE_TABLE_NAME = 'bundle_advancedobjectsearch_update_queue';
    const PERMISSION_KEY = 'bundle_advancedsearch_search';

    protected function beforeInstallMigration()
    {
        if (! file_exists(PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch")) {
            \Pimcore\File::mkdir(PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch");
            copy(
                __DIR__ . "/Resources/install/config.php",
                PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch/config.php"
            );
        }
    }

    protected function afterInstallMigration()
    {
        $key = self::PERMISSION_KEY;
        $definition = Definition::getByKey($key);

        if (! $definition) {
            $permission = new Definition();
            $permission->setKey($key);

            $res = new Definition\Dao();
            $res->configure();
            $res->setModel($permission);
            $res->save();
        }
    }

    public function migrateInstall(Schema $schema, Version $version)
    {
        if (! $schema->hasTable(self::QUEUE_TABLE_NAME)) {
            $queueTable = $schema->createTable(self::QUEUE_TABLE_NAME);
            $queueTable->addColumn('o_id', 'bigint', ['default' => 0, 'notnull' => true]);
            $queueTable->addColumn('classId', 'integer', ['notnull' => false]);
            $queueTable->addColumn('in_queue', 'boolean', ['notnull' => false]);
            $queueTable->addColumn('worker_timestamp', 'bigint', ['length' => 20, 'notnull' => false]);
            $queueTable->addColumn('worker_id', 'string', ['length' => 20, 'notnull' => false]);
            $queueTable->setPrimaryKey(['o_id']);
        }

        if (! $schema->hasTable(SavedSearch\Dao::TABLE_NAME)) {
            $savedSearchTable = $schema->createTable(SavedSearch\Dao::TABLE_NAME);
            $savedSearchTable->addColumn('id', 'bigint', ['length' => 20, 'autoincrement' => true, 'notnull' => true]);
            $savedSearchTable->addColumn('name', 'string', ['length' => 255, 'notnull' => false]);
            $savedSearchTable->addColumn('description', 'string', ['length' => 255, 'notnull' => false]);
            $savedSearchTable->addColumn('category', 'string', ['length' => 255, 'notnull' => false]);
            $savedSearchTable->addColumn('ownerId', 'bigint', ['length' => 20, 'notnull' => false]);
            $savedSearchTable->addColumn('config', 'text', ['notnull' => false]);
            $savedSearchTable->addColumn('sharedUserIds', 'string', ['length' => 1000, 'notnull' => false]);
            $savedSearchTable->addColumn('shortCutUserIds', 'text', ['notnull' => false]);
            $savedSearchTable->setPrimaryKey(['id']);
        }
    }

    public function migrateUninstall(Schema $schema, Version $version)
    {
        $tables = [
            self::QUEUE_TABLE_NAME,
            SavedSearch\Dao::TABLE_NAME,
        ];

        foreach ($tables as $tableName) {
            if ($schema->hasTable($tableName)) {
                $schema->dropTable($tableName);
            }
        }

        $key = self::PERMISSION_KEY;
        $this->connection->query("DELETE FROM users_permission_definitions WHERE `key` = '{$key}'");
    }


    public function needsReloadAfterInstall()
    {
        return true;
    }
}
