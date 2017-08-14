<?php

namespace AdvancedObjectSearchBundle\Model\SavedSearch\Listing;

use ESBackendSearch\SavedSearch;
use Pimcore\Model;

class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of tags for the specifies parameters, returns an array of Element\Tag elements
     *
     * @return array
     */
    public function load()
    {
        $searchIds = $this->db->fetchCol("SELECT id FROM " . $this->db->quoteIdentifier(\ESBackendSearch\SavedSearch\Dao::TABLE_NAME) . " " . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $searches = array();
        foreach ($searchIds as $id) {
            if ($savedSearch = SavedSearch::getById($id)) {
                $searches[] = $savedSearch;
            }
        }

        $this->model->setSavedSearches($searches);
        return $searches;
    }


    public function loadIdList()
    {
        $searchIds = $this->db->fetchCol("SELECT id FROM " . $this->db->quoteIdentifier(\ESBackendSearch\SavedSearch\Dao::TABLE_NAME) . " " . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $searchIds;
    }

    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM " . $this->db->quoteIdentifier(\ESBackendSearch\SavedSearch\Dao::TABLE_NAME) . " " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
