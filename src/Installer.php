<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace AdvancedObjectSearchBundle;

use AdvancedObjectSearchBundle\Model\SavedSearch;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Model\User\Permission\Definition;

class Installer extends SettingsStoreAwareInstaller
{
    const QUEUE_TABLE_NAME = 'bundle_advancedobjectsearch_update_queue';
    const PERMISSION_KEY = 'bundle_advancedsearch_search';

    protected function installPermissions(): void
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

    /**
     * @throws Exception
     */
    public function install(): void
    {
        /**
         * The simple backend search can be deactivated from Pimcore 11 on. But it is necessary for the advanced object search,
         * so we have to make sure that it is activated & installed.
         */
        $simpleBackendSearchInstaller = \Pimcore::getContainer()->get(\Pimcore\Bundle\SimpleBackendSearchBundle\Installer::class);
        if (!$simpleBackendSearchInstaller->isInstalled()) {
            $simpleBackendSearchInstaller->install();
        }

        /**
         * @var Connection $db
         */
        $db = Db::get();
        $currentSchema = $db->createSchemaManager()->introspectSchema();
        $schema = $db->createSchemaManager()->introspectSchema();

        if (! $schema->hasTable(self::QUEUE_TABLE_NAME)) {
            $queueTable = $schema->createTable(self::QUEUE_TABLE_NAME);
            $queueTable->addColumn('id', 'bigint', ['default' => 0, 'notnull' => true]);
            $queueTable->addColumn('classId', 'integer', ['notnull' => false]);
            $queueTable->addColumn('in_queue', 'boolean', ['notnull' => false]);
            $queueTable->addColumn('worker_timestamp', 'bigint', ['length' => 20, 'notnull' => false]);
            $queueTable->addColumn('worker_id', 'string', ['length' => 20, 'notnull' => false]);
            $queueTable->setPrimaryKey(['id']);
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
            $savedSearchTable->addColumn('shareGlobally', 'boolean', ['default' => null, 'notnull' => false]);
            $savedSearchTable->addIndex(['shareGlobally'], 'shareGlobally');
            $savedSearchTable->setPrimaryKey(['id']);
        }

        $sqlStatements = $currentSchema->getMigrateToSql($schema, $db->getDatabasePlatform());
        if (!empty($sqlStatements)) {
            $db->executeStatement(implode(';', $sqlStatements));
        }

        $this->installPermissions();

        parent::install();
    }

    /**
     * @throws Exception
     */
    public function uninstall(): void
    {
        /**
         * @var Connection $db
         */
        $db = Db::get();
        $currentSchema = $db->createSchemaManager()->introspectSchema();
        $schema = $db->createSchemaManager()->introspectSchema();

        $tables = [
            self::QUEUE_TABLE_NAME,
            SavedSearch\Dao::TABLE_NAME,
        ];

        foreach ($tables as $tableName) {
            if ($schema->hasTable($tableName)) {
                $schema->dropTable($tableName);
            }
        }

        $sqlStatements = $currentSchema->getMigrateToSql($schema, $db->getDatabasePlatform());
        if (!empty($sqlStatements)) {
            $db->executeStatement(implode(';', $sqlStatements));
        }

        $key = self::PERMISSION_KEY;
        $db->executeStatement("DELETE FROM users_permission_definitions WHERE `key` = '{$key}'");

        parent::uninstall();
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    public function getLastMigrationVersionClassName(): ?string
    {
        return 'AdvancedObjectSearchBundle\\Migrations\\PimcoreX\\Version20221130130306';
    }
}
