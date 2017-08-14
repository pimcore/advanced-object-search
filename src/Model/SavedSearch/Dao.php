<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace AdvancedObjectSearchBundle\Model\SavedSearch;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao
{
    const TABLE_NAME = "bundle_advancedobjectsearch_savedsearch";

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT * FROM " . $this->db->quoteIdentifier(self::TABLE_NAME) . " WHERE id = ?", $id);
        if (!$data["id"]) {
            throw new \Exception("SavedSearch item with id " . $id . " not found");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return bool
     */
    public function save()
    {
        $this->db->beginTransaction();
        try {
            $dataAttributes = get_object_vars($this->model);

            $data = [];
            foreach ($dataAttributes as $key => $value) {
                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME))) {
                    if (in_array($key, ["sharedUserIds", "shortCutUserIds"])) {
                        // sharedUserIds and shortCustUserIds are stored as csv
                        if (is_array($value)) {
                            $value = "," . implode(",", $value) . ",";
                        }
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->insertOrUpdate(self::TABLE_NAME, $data);

            $lastInsertId = $this->db->lastInsertId();
            if (!$this->model->getId() && $lastInsertId) {
                $this->model->setId($lastInsertId);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->beginTransaction();
        try {
            $this->db->delete(self::TABLE_NAME, ['id' => $this->model->getId()]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

}
