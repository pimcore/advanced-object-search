<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use DeepCopy\Filter\Filter;
use ESBackendSearch\FieldSelectionInformation;
use ESBackendSearch\FilterEntry;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;
use function Sodium\add;

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