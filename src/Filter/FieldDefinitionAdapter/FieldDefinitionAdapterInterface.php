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

use AdvancedObjectSearchBundle\Filter\FieldSelectionInformation;
use AdvancedObjectSearchBundle\Service;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

interface FieldDefinitionAdapterInterface
{
    const ES_MAPPING_PROPERTY_STANDARD = 'standard';
    const ES_MAPPING_PROPERTY_NOT_INHERITED = 'notInherited';

    /**
     * @param Service $service
     * @param LocaleServiceInterface $locale
     */
    public function __construct(Service $service, LocaleServiceInterface $locale);

    /**
     * @param Data $fieldDefinition
     */
    public function setFieldDefinition(Data $fieldDefinition);

    /**
     * @param bool $considerInheritance
     */
    public function setConsiderInheritance(bool $considerInheritance);

    /**
     * @return array
     */
    public function getESMapping();

    /**
     * @param Concrete $object
     *
     * @return array
     */
    public function getIndexData($object);

    /**
     * @param array|string|int|bool $fieldFilter - see concrete implementations for format
     * @param string $path - sub path for nested objects (only needed internally)
     * @param bool $ignoreInheritance - if true inheritance is not considered during query
     *
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $ignoreInheritance = false, $path = '');

    /**
     * @param mixed $fieldFilter - see concrete implementations for format
     * @param bool $ignoreInheritance - if true inheritance is not considered during query
     * @param string $path - sub path for nested objects (only needed internally)
     *
     * @return ExistsQuery
     */
    public function getExistsFilter($fieldFilter, $ignoreInheritance = false, $path = '');

    /**
     * returns selectable fields with their type information for search frontend
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformation();
}
