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
            $this->parameters = new ParameterBag(json_decode($request->request->getString('customFilter'), true) ?: []);
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
    public static function getSubscribedEvents(): array
    {
        return [
            AdvancedObjectSearchEvents::SEARCH_FILTER => [
                ['onIndexSearch', 10],
            ],

            AdvancedObjectSearchEvents::LISTING_FILER => [
                ['onListing', 10]
            ]
        ];
    }

    public function onIndexSearch(FilterSearchEvent $event): void
    {
        if ($this->supports()) {
            $this->addIndexSearchFilter($event);
        }
    }

    public function onListing(FilterListingEvent $event): void
    {
        if ($this->supports()) {
            $this->addListingFiler($event);
        }
    }

    abstract protected function supports(): bool;

    protected function addIndexSearchFilter(FilterSearchEvent $event): void
    {
    }

    protected function addListingFiler(FilterListingEvent $event): void
    {
    }
}
