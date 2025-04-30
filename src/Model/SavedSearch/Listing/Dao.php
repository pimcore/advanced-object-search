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

namespace AdvancedObjectSearchBundle\Model\SavedSearch\Listing;

use AdvancedObjectSearchBundle\Model\SavedSearch;
use Doctrine\DBAL\Exception;
use Pimcore\Model;

/**
 * @property SavedSearch\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of tags for the specifies parameters, returns an array of Element\Tag elements
     *
     * @return array
     *
     * @throws Exception
     */
    public function load(): array
    {
        $searchIds = $this->db->fetchFirstColumn('SELECT id FROM ' . $this->db->quoteIdentifier(SavedSearch\Dao::TABLE_NAME) . ' ' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $searches = [];
        foreach ($searchIds as $id) {
            if ($savedSearch = SavedSearch::getById($id)) {
                $searches[] = $savedSearch;
            }
        }

        $this->model->setSavedSearches($searches);

        return $searches;
    }

    /**
     * @throws Exception
     */
    public function loadIdList()
    {
        return $this->db->fetchFirstColumn('SELECT id FROM ' . $this->db->quoteIdentifier(SavedSearch\Dao::TABLE_NAME) . ' ' . $this->getCondition() . $this->getGroupBy() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
    }

    public function getTotalCount(): int
    {
        $amount = 0;

        try {
            $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM ' . $this->db->quoteIdentifier(SavedSearch\Dao::TABLE_NAME) . ' ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
        }

        return $amount;
    }
}
