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
