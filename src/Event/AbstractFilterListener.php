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

namespace AdvancedObjectSearchBundle\Event;

use AdvancedObjectSearchBundle\Service;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractFilterListener implements EventSubscriberInterface
{
    /**
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * @var Service
     */
    protected $service;

    public function __construct(RequestStack $requestStack, Service $service)
    {
        $request = $requestStack->getCurrentRequest();

        $this->service = $service;
        if ($request) {
            $this->parameters = new ParameterBag(json_decode($request->get('customFilter'), true) ?: []);
        } else {
            $this->parameters = new ParameterBag([]);
        }
    }

    /**
     * @return ParameterBag
     */
    protected function getParameters(): ParameterBag
    {
        return $this->parameters;
    }

    /**
     * @return Service
     */
    protected function getService(): Service
    {
        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AdvancedObjectSearchEvents::ELASITIC_FILTER => [
                ['onElasticSearch', 10],
            ],

            AdvancedObjectSearchEvents::LISTING_FILER => [
                ['onListing', 10]
            ]
        ];
    }

    public function onElasticSearch(FilterSearchEvent $event)
    {
        if ($this->supports()) {
            $this->addElasticSearchFilter($event);
        }
    }

    public function onListing(FilterListingEvent $event)
    {
        if ($this->supports()) {
            $this->addListingFiler($event);
        }
    }

    /**
     * @return bool
     */
    abstract protected function supports(): bool;

    /**
     * @param FilterSearchEvent $event
     *
     * @return void
     */
    protected function addElasticSearchFilter(FilterSearchEvent $event)
    {
    }

    /**
     * @param FilterListingEvent $event
     *
     * @return void
     */
    protected function addListingFiler(FilterListingEvent $event)
    {
    }
}
