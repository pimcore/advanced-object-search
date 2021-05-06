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

namespace AdvancedObjectSearchBundle\Migrations\PimcoreX;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\BundleAwareMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210305134111 extends BundleAwareMigration
{
    protected function getBundleName(): string
    {
        return 'AdvancedObjectSearchBundle';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // nothing to do
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // nothing to do
    }
}
