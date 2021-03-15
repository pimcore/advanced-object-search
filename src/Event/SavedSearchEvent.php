<?php

namespace AdvancedObjectSearchBundle\Event;

use AdvancedObjectSearchBundle\Model\SavedSearch;
use Symfony\Contracts\EventDispatcher\Event;

class SavedSearchEvent extends Event
{
    /**
     * @var SavedSearch
     */
    protected $search;

    public function __construct(SavedSearch $search)
    {
        $this->search = $search;
    }

    /**
     * @return Search
     */
    public function getSavedSearch(): SavedSearch
    {
        return $this->search;
    }
}
