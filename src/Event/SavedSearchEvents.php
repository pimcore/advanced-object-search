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

final class SavedSearchEvents
{
    /**
     * @Event("AdvancedObjectSearchBundle\Event\SavedSearchEvent")
     */
    const PRE_SAVE = 'advanced_object_search.saved_search.preSave';

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SavedSearchEvent")
     */
    const POST_SAVE = 'advanced_object_search.saved_search.postSave';

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SavedSearchEvent")
     */
    const PRE_DELETE = 'advanced_object_search.saved_search.preDelete';

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SavedSearchEvent")
     */
    const POST_DELETE = 'advanced_object_search.saved_search.postDelete';
}
