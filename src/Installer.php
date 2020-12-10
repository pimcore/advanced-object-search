<?php

namespace AdvancedObjectSearchBundle;

use AdvancedObjectSearchBundle\Model\SavedSearch;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db\Connection;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Model\User\Permission\Definition;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Installer extends AbstractInstaller
{
    const QUEUE_TABLE_NAME = 'bundle_advancedobjectsearch_update_queue';
    const PERMISSION_KEY = 'bundle_advancedsearch_search';

    /**
     * @var BundleInterface
     */
    protected $bundle;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var Schema
     */
    protected $schema;

    public function __construct(
        BundleInterface $bundle,
        ConnectionInterface $connection
    ) {
        $this->bundle = $bundle;
        $this->db = $connection;
        if ($this->db instanceof Connection) {
            $this->schema = $this->db->getSchemaManager()->createSchema();
        }

        parent::__construct();
    }

    private function installConfigs()
    {
        if (! file_exists(PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch")) {
            \Pimcore\File::mkdir(PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch");
            copy(
                __DIR__ . "/Resources/install/config.php",
                PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch/config.php"
            );
        }
    }

    private function installPermissions()
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

    public function install()
    {
        $this->installConfigs();
        $this->installTables();
        $this->installPermissions();
    }

    private function installTables()
    {
        if (!$this->schema->hasTable(self::QUEUE_TABLE_NAME)) {
            $queueTable = $this->schema->createTable(self::QUEUE_TABLE_NAME);
            $queueTable->addColumn('o_id', 'bigint', ['default' => 0, 'notnull' => true]);
            $queueTable->addColumn('classId', 'integer', ['notnull' => false]);
            $queueTable->addColumn('in_queue', 'boolean', ['notnull' => false]);
            $queueTable->addColumn('worker_timestamp', 'bigint', ['length' => 20, 'notnull' => false]);
            $queueTable->addColumn('worker_id', 'string', ['length' => 20, 'notnull' => false]);
            $queueTable->setPrimaryKey(['o_id']);
        }

        if (! $this->schema->hasTable(SavedSearch\Dao::TABLE_NAME)) {
            $savedSearchTable = $this->schema->createTable(SavedSearch\Dao::TABLE_NAME);
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

    public function uninstall()
    {
        $tables = [
            self::QUEUE_TABLE_NAME,
            SavedSearch\Dao::TABLE_NAME,
        ];

        foreach ($tables as $tableName) {
            if ($this->schema->hasTable($tableName)) {
                $this->schema->dropTable($tableName);
            }
        }

        $key = self::PERMISSION_KEY;
        $this->db->executeQuery("DELETE FROM users_permission_definitions WHERE `key` = '{$key}'");
    }


    public function needsReloadAfterInstall()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        $installed = false;
        try {
            // check if if first permission is installed
            $installed = $this->db->fetchOne('SELECT `key` FROM users_permission_definitions WHERE `key` = :key', [
                'key' => self::PERMISSION_KEY,
            ]);
        } catch (\Exception $e) {
            // nothing to do
        }

        return (bool) $installed;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled()
    {
        return !$this->isInstalled();
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled()
    {
        return $this->isInstalled();
    }
}
