<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace AdvancedObjectSearchBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\Tool\SettingsStore;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210305134111 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function up(Schema $schema)
    {
        $db = Db::get();
        $entry = $db->fetchAssociative(
            'SELECT * FROM pimcore_migrations WHERE migration_set = ? AND version = ?',
            ['AdvancedObjectSearchBundle', '00000001']
        );

        if ($entry && !empty($entry['migrated_at'])) {
            SettingsStore::set('BUNDLE_INSTALLED__AdvancedObjectSearchBundle\\AdvancedObjectSearchBundle', true, 'bool', 'pimcore');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
