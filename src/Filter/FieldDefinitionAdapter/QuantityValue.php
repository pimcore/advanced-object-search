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

use AdvancedObjectSearchBundle\Filter\FieldSelectionInformation;
use AdvancedObjectSearchBundle\Filter\FilterEntry;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;

class QuantityValue extends Numeric implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "quantityValue";

    /**
     * @return array
     */
    public function getESMapping() {
        if($this->considerInheritance) {
            return [
                $this->fieldDefinition->getName(),
                [
                    'properties' => [
                        self::ES_MAPPING_PROPERTY_STANDARD => [
                            'properties' => [
                                'value' => [
                                    'type' => 'float',
                                ],
                                'unit' => [
                                    'type' => 'integer',
                                ]
                            ]
                        ],
                        self::ES_MAPPING_PROPERTY_NOT_INHERITED => [
                            'properties' => [
                                'value' => [
                                    'type' => 'float',
                                ],
                                'unit' => [
                                    'type' => 'integer',
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        } else {
            return [
                $this->fieldDefinition->getName(),
                [
                    'properties' => [
                        'value' => [
                            'type' => 'float',
                        ],
                        'unit' => [
                            'type' => 'integer',
                        ]
                    ]

                ]
            ];
        }
    }


    /**
     * @param $fieldFilter
     *
     * filter field format as follows:
     *   - simple array with number/unitID like
     *       ["value" => 234.54, "unit" => 3]   --> creates TermQuery
     *   - array with gt, gte, lt, lte like
     *      ["value" => ["gte" => 40, "lte" => 45], "unit" => 3] --> creates RangeQuery
     *
     * @param bool $ignoreInheritance
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $ignoreInheritance = false, $path = "") {
        $boolQuery = new BoolQuery();
        if(is_array($fieldFilter) && is_array($fieldFilter['value'])) {
            $boolQuery->add(new RangeQuery($path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance) . ".value", $fieldFilter["value"]));
        } else {
            $boolQuery->add(new TermQuery($path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance) . ".value", $fieldFilter["value"]));
        }
        $boolQuery->add(new TermQuery($path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance) . ".unit", $fieldFilter["unit"]));
        return $boolQuery;
    }

    /**
     * returns selectable fields with their type information for search frontend
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformation()
    {
        return [new FieldSelectionInformation(
            $this->fieldDefinition->getName(),
            $this->fieldDefinition->getTitle(),
            $this->fieldType,
            [
                'operators' => ['lt', 'lte', 'eq', 'gte', 'gt', FilterEntry::EXISTS, FilterEntry::NOT_EXISTS ],
                'classInheritanceEnabled' => $this->considerInheritance,
                'units' => $this->fieldDefinition->getValidUnits()
            ]
        )];
    }

    /**
     * @param Concrete $object
     * @param bool $ignoreInheritance
     */
    protected function doGetIndexDataValue($object, $ignoreInheritance = false) {
        $inheritanceBackup = null;
        if($ignoreInheritance) {
            $inheritanceBackup = AbstractObject::getGetInheritedValues();
            AbstractObject::setGetInheritedValues(false);
        }

        $value = $this->fieldDefinition->getForWebserviceExport($object);
        unset($value['unitAbbreviation']);


        if($ignoreInheritance) {
            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }

        return $value;
    }


}
