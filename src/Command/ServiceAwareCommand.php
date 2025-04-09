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

namespace AdvancedObjectSearchBundle\Command;

use AdvancedObjectSearchBundle\Service;
use Pimcore\Console\AbstractCommand;
use Symfony\Contracts\Service\Attribute\Required;

abstract class ServiceAwareCommand extends AbstractCommand
{
    /**
     * @var Service
     */
    protected $service;

    /**
     * @return Service
     */
    public function getService(): Service
    {
        return $this->service;
    }

    /**
     * @param Service $service
     */
    #[Required]
    public function setService(Service $service): void
    {
        $this->service = $service;
    }
}
