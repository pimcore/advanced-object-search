<?php

namespace AdvancesObjectSearchBundle\Model\SavedSearch;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing
{

    /**
     * Contains the results of the list. They are all an instance of SavedSearch
     *
     * @var array
     */
    public $savedSearches = array();

    /**
     * Tests if the given key is an valid order key to sort the results
     *
     * @return boolean
     */
    public function isValidOrderKey($key)
    {
        return true;
    }

    /**
     * @param $savedSearches
     * @return $this
     */
    public function setSavedSearches($savedSearches)
    {
        $this->savedSearches = $savedSearches;
        return $this;
    }

    /**
     * @return array
     */
    public function getSavedSearches()
    {
        return $this->savedSearches;
    }
}
