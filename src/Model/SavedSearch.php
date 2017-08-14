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


namespace AdvancedObjectSearchBundle\Model;

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
     * @var array
     */
    public $sharedUserIds;

    /**
     * @var array
     */
    public $shortCutUserIds;

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

    /**
     * @return array
     */
    public function getSharedUserIds()
    {
        return $this->sharedUserIds;
    }

    /**
     * @return Model\User[]
     */
    public function getSharedUsers() {
        $users = [];
        foreach($this->sharedUserIds as $id) {
            $users[] = Model\User::getById($id);
        }
        return $users;
    }

    /**
     * @param mixed $sharedUserIds
     */
    public function setSharedUserIds($sharedUserIds)
    {
        if(is_string($sharedUserIds) && !empty($sharedUserIds)) {
            $sharedUserIds = array_values(array_filter(explode(",", $sharedUserIds)));
        }

        $this->sharedUserIds = $sharedUserIds;
    }

    /**
     * @return array
     */
    public function getShortCutUserIds()
    {
        return $this->shortCutUserIds;
    }

    /**
     * @param mixed $shortCutUserIds
     */
    public function setShortCutUserIds($shortCutUserIds)
    {
        if(is_string($shortCutUserIds) && !empty($shortCutUserIds)) {
            $shortCutUserIds = explode(",", $shortCutUserIds);
        }

        $this->shortCutUserIds = $shortCutUserIds;
    }


    /**
     * @param Model\User $user
     * @return bool
     */
    public function isInShortCutsForUser(Model\User $user) {
        $userId = $user->getId();
        return $this->getShortCutUserIds() && in_array($userId, $this->getShortCutUserIds());
    }

    /**
     * @param Model\User $user
     */
    public function addShortCutForUser(Model\User $user) {
        $userId = $user->getId();

        $shortCutUserIds = $this->getShortCutUserIds();
        if(!$shortCutUserIds) {
            $shortCutUserIds = [];
        }
        $shortCutUserIds[] = $userId;
        $this->setShortCutUserIds(array_unique($shortCutUserIds));
    }

    /**
     * @param Model\User $user
     */
    public function removeShortCutForUser(Model\User $user) {
        $userId = $user->getId();
        $shortCutUserIds = $this->getShortCutUserIds();
        $shortCutUserIds = array_flip($shortCutUserIds);
        unset($shortCutUserIds[$userId]);
        $this->setShortCutUserIds(array_filter(array_keys($shortCutUserIds)));
    }



}
