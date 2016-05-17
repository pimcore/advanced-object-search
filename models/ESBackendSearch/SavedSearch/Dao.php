<?php

namespace ESBackendSearch\SavedSearch;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;

class Dao extends Model\Dao\AbstractDao
{
    const TABLE_NAME = "plugin_esbackendsearch_savedsearch";

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
//            $this->db->delete("tags_assignment", $this->db->quoteInto("tagid = ?", $this->model->getId()));
//            $this->db->delete("tags_assignment", $this->db->quoteInto("tagid IN (SELECT id FROM tags WHERE idPath LIKE ?)", $this->model->getIdPath() . $this->model->getId() . "/%"));

            $this->db->delete(self::TABLE_NAME, $this->db->quoteInto("id = ?", $this->model->getId()));

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

}
