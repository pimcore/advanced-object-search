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

final class AdvancedObjectSearchEvents
{
    /**
     * @Event("AdvancedObjectSearchBundle\Event\SearchEvent")
     */
    const ELASITIC_FILTER = 'advanced_object_search.elastic_filter';

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SearchEvent")
     */
    const LISTING_FILER = 'advanced_object_search.listing_filter';
}
