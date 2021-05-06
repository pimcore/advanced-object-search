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

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;

class AdvancedManyToManyRelation extends ManyToOneRelation implements FieldDefinitionAdapterInterface
{
    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = 'advancedManyToManyRelation';

    /**
     * @inheritDoc
     */
    protected function doGetIndexDataValue($object, $ignoreInheritance = false)
    {
        $value = parent::doGetIndexDataValue($object, $ignoreInheritance);

        $filteredValues = array_map(function ($item) {
            return $item['element'] ?? $item;
        }, $value);

        return $filteredValues;
    }
}
