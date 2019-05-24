<?php
/**
 * Created by PhpStorm.
 * User: cfasching
 * Date: 24.05.2019
 * Time: 15:02
 */

namespace AdvancedObjectSearchBundle\Command;


use AdvancedObjectSearchBundle\Service;
use Pimcore\Console\AbstractCommand;

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
     * @required
     */
    public function setService(Service $service): void
    {
        $this->service = $service;
    }

}