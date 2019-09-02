<?php

namespace AdvancedObjectSearchBundle\Event;

final class AdvancedObjectSearchEvents
{
    /**
     * @Event("AdvancedObjectSearchBundle\Event\SearchEvent")
     */
    const ELASITIC_FILTER = "advanced_object_search.elastic_filter";

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SearchEvent")
     */
    const LISTING_FILER = "advanced_object_search.listing_filter";
}