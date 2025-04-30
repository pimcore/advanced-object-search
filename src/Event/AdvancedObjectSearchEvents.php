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

final class AdvancedObjectSearchEvents
{
    /**
     * @Event("AdvancedObjectSearchBundle\Event\SearchEvent")
     */
    public const SEARCH_FILTER = 'advanced_object_search.search_filter';

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SearchEvent")
     */
    public const LISTING_FILER = 'advanced_object_search.listing_filter';
}
