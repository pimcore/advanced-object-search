<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use DeepCopy\Filter\Filter;
use ESBackendSearch\FieldSelectionInformation;
use ESBackendSearch\FilterEntry;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;

class Datetime extends Numeric implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "datetime";

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
                            'type' => 'date',
                        ],
                        self::ES_MAPPING_PROPERTY_NOT_INHERITED => [
                            'type' => 'date',
                        ]
                    ]
                ]
            ];
        } else {
            return [
                $this->fieldDefinition->getName(),
                [
                    'type' => 'date',
                ]
            ];
        }
    }


    /**
     * @param $fieldFilter
     *
     * filter field format as follows:
     *   - simple date like
     *       2017-02-26   --> creates TermQuery
     *   - array with gt, gte, lt, lte like
     *      ["gte" => 2017-02-26, "lte" => 2017-05-26] --> creates RangeQuery
     *
     * @param bool $ignoreInheritance
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $ignoreInheritance = false, $path = "") {
        if(is_array($fieldFilter)) {

            foreach($fieldFilter as &$value) {
                $datetime = new \DateTime($value);
                $value = $datetime->format(\DateTime::ISO8601);
            }

            return new RangeQuery($path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance), $fieldFilter);
        } else {
            $datetime = new \DateTime($fieldFilter);
            $datetime = $datetime->format(\DateTime::ISO8601);
            return new TermQuery($path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance), $datetime);
        }
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

        $value = null;

        $getter = "get" . $this->fieldDefinition->getName();
        $valueObject = $object->$getter();
        if($valueObject) {
            $value = $valueObject->format(\DateTime::ISO8601);
        }

        if($ignoreInheritance) {
            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }

        return $value;
    }

}