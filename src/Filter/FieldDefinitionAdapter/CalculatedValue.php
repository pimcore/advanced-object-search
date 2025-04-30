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

use Pimcore\Model\DataObject\Concrete;

class CalculatedValue extends DefaultAdapter implements FieldDefinitionAdapterInterface
{
    /**
     * @param Concrete $object
     * @param bool $ignoreInheritance
     *
     * @return string
     */
    protected function doGetIndexDataValue($object, $ignoreInheritance = false)
    {
        $name = $this->fieldDefinition->getName();
        $value = $this->loadRawDataFromContainer($object, $name);

        return (string) $value;
    }
}
