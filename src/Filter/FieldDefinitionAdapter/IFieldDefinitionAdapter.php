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


namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;

@trigger_error(
    'Interface `AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter\IFieldDefinitionAdapterInterface` is deprecated since version 1.3.0 and will be removed in 2.0.0. ' .
    'Use `' . \AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter\FieldDefinitionAdapterInterface::class . '` instead.',
    E_USER_DEPRECATED
);

/**
 * @deprecated use \AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter\FieldDefinitionAdapterInterface instead
 */
interface IFieldDefinitionAdapter extends FieldDefinitionAdapterInterface
{

}
