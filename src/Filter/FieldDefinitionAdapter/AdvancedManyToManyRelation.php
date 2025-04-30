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
