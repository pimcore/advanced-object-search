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

use ONGR\ElasticsearchDSL\Search;
use Symfony\Contracts\EventDispatcher\Event;

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
