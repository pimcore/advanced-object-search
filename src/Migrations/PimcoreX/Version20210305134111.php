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
