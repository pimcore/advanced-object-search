<?php

namespace ESBackendSearch;

use Pimcore\Model;

class SavedSearch extends Model\AbstractModel
{

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $category;

    /**
     * @var string
     */
    public $description;

    /**
     * @var Model\User
     */
    public $owner;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var string
     */
    public $config;


    /**
     * @static
     * @param $id
     * @return SavedSearch
     */
    public static function getById($id)
    {
        try {
            $tag = new self();
            $tag->getDao()->getById($id);

            return $tag;
        } catch (\Exception $e) {
            return null;
        }
    }


    public function save()
    {
        $this->getDao()->save();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return SavedSearch
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return SavedSearch
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return SavedSearch
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Model\User
     */
    public function getOwner()
    {
        if(empty($this->owner)) {
            $this->owner = Model\User::getById($this->ownerId);
        }
        return $this->owner;
    }

    /**
     * @param Model\User $owner
     * @return SavedSearch
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
        $this->ownerId = $owner->getId();
        return $this;
    }

    /**
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $config
     * @return SavedSearch
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return SavedSearch
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @param int $ownerId
     * @return SavedSearch
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
        $this->owner = null;
        return $this;
    }

}
