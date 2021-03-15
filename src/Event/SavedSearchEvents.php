<?php

namespace AdvancedObjectSearchBundle\Event;

final class SavedSearchEvents
{
    /**
     * @Event("AdvancedObjectSearchBundle\Event\SavedSearchEvent")
     */
    const PRE_SAVE = "advanced_object_search.saved_search.preSave";

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SavedSearchEvent")
     */
    const POST_SAVE = "advanced_object_search.saved_search.postSave";

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SavedSearchEvent")
     */
    const PRE_DELETE = "advanced_object_search.saved_search.preDelete";

    /**
     * @Event("AdvancedObjectSearchBundle\Event\SavedSearchEvent")
     */
    const POST_DELETE = "advanced_object_search.saved_search.postDelete";
}
