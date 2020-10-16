<?php

namespace AdvancedObjectSearchBundle\Event;

use Pimcore\Model\DataObject\Listing;
use Symfony\Contracts\EventDispatcher\Event;

class FilterListingEvent extends Event
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
