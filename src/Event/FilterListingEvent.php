<?php

namespace AdvancedObjectSearchBundle\Event;

use Pimcore\Model\DataObject\Listing;
use Symfony\Component\EventDispatcher\GenericEvent;

class FilterListingEvent extends GenericEvent
{
    /**
     * @var Listing
     */
    protected $listing;

    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    /**
     * @return Listing
     */
    public function getListing(): Listing
    {
        return $this->listing;
    }
}
