<?php

namespace AdvancedObjectSearchBundle\Event;

use ONGR\ElasticsearchDSL\Search;
use Symfony\Component\EventDispatcher\Event;

class FilterSearchEvent extends Event
{
    /**
     * @var Search
     */
    protected $search;

    public function __construct(Search $search)
    {
        $this->search = $search;
    }

    /**
     * @return Search
     */
    public function getSearch(): Search
    {
        return $this->search;
    }
}