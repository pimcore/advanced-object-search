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
