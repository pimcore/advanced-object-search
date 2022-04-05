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
     * @return SavedSearch
     */
    public function getSavedSearch(): SavedSearch
    {
        return $this->search;
    }
}
