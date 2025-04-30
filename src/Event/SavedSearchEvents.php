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
