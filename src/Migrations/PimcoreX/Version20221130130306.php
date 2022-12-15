<?php

declare(strict_types=1);

namespace AdvancedObjectSearchBundle\Migrations\PimcoreX;

use Doctrine\DBAL\Schema\Schema;
use AdvancedObjectSearchBundle\Installer;
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
