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

use AdvancedObjectSearchBundle\Model\SavedSearch;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;

class Version20191218105114 extends AbstractPimcoreMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $execTable = $schema->getTable(SavedSearch\Dao::TABLE_NAME);
        $execTable->addColumn('shareGlobally', 'boolean', ['default' => null, 'notnull' => false]);
        $execTable->addIndex(['shareGlobally'], 'shareGlobally');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $execTable = $schema->getTable(SavedSearch\Dao::TABLE_NAME);
        $execTable->dropColumn('shareGlobally');
    }
}
