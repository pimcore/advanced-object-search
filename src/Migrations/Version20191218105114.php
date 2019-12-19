<?php

namespace AdvancedObjectSearchBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use AdvancedObjectSearchBundle\Model\SavedSearch;

class Version20191218105114 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $execTable = $schema->getTable(SavedSearch\Dao::TABLE_NAME);
        $execTable->addColumn("shareGlobally", "boolean", ['default' => null, 'notnull' => false]);
        $execTable->addIndex(["shareGlobally"], "shareGlobally");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $execTable = $schema->getTable(SavedSearch\Dao::TABLE_NAME);
        $execTable->dropColumn("shareGlobally");
    }
}
