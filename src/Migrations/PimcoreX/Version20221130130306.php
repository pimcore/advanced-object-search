<?php

declare(strict_types=1);

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

namespace AdvancedObjectSearchBundle\Migrations\PimcoreX;

use AdvancedObjectSearchBundle\Installer;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\BundleAwareMigration;

class Version20221130130306 extends BundleAwareMigration
{
    protected function getBundleName(): string
    {
        return 'AdvancedObjectSearchBundle';
    }

    public function getDescription(): string
    {
        return 'Migrate `o_id` column to `id` in queue table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(Installer::QUEUE_TABLE_NAME)->getName();
        $query = 'ALTER TABLE %s RENAME COLUMN `o_id` TO `id`;';

        $this->addSql(sprintf($query, $table));
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(Installer::QUEUE_TABLE_NAME)->getName();
        $query = 'ALTER TABLE %s RENAME COLUMN `id` TO `o_id`;';

        $this->addSql(sprintf($query, $table));
    }
}
