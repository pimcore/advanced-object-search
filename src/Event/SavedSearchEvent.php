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
