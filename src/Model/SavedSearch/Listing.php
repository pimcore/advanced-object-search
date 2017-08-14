<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace AdvancedObjectSearchBundle\Model\SavedSearch;

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
