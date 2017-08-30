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
use AdvancedObjectSearchBundle\Service;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

class DefaultAdapter implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "default";

    /**
     * @var Data
     */
    protected $fieldDefinition;

    /**
     * @var bool
     */
    protected $considerInheritance;

    /**
     * @var Service
     */
    protected $service;

    /**
     * DefaultAdapter constructor.
     * @param Service $service
     */
    public function __construct(Service $service) {
        $this->service = $service;
    }

    /**
     * @param Data $fieldDefinition
     */
    public function setFieldDefinition(Data $fieldDefinition) {
        $this->fieldDefinition = $fieldDefinition;
    }

    /**
     * @param bool $considerInheritance
     */
    public function setConsiderInheritance(bool $considerInheritance) {
        $this->considerInheritance = $considerInheritance;
    }


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
                            'type' => 'string',
                            'fields' => [
                                "raw" =>  [ "type" => "string", "index" => "not_analyzed" ]
                            ]
                        ],
                        self::ES_MAPPING_PROPERTY_NOT_INHERITED => [
                            'type' => 'string',
                            'fields' => [
                                "raw" =>  [ "type" => "string", "index" => "not_analyzed" ]
                            ]
                        ]
                    ]
                ]
            ];
        } else {
            return [
                $this->fieldDefinition->getName(),
                [
                    'type' => 'string',
                    'fields' => [
                        "raw" =>  [ "type" => "string", "index" => "not_analyzed" ]
                    ]
                ]
            ];
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

        $value = $this->fieldDefinition->getForWebserviceExport($object);

        if($ignoreInheritance) {
            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }

        return (string) $value;
    }

    /**
     * @param Concrete $object
     * @return mixed
     */
    public function getIndexData($object) {

        $value = $this->doGetIndexDataValue($object, false);

        if($this->considerInheritance) {
            $notInheritedValue = $this->doGetIndexDataValue($object, true);

            $returnValue = null;
            if($value) {
                $returnValue[self::ES_MAPPING_PROPERTY_STANDARD] = $value;
            }

            if($notInheritedValue) {
                $returnValue[self::ES_MAPPING_PROPERTY_NOT_INHERITED] = $notInheritedValue;
            }

            return $returnValue;

        } else {

            if($value) {
                return $value;
            } else {
                return null;
            }
        }
    }


    protected function buildQueryFieldPostfix($ignoreInheritance = false) {
        $postfix = "";

        if($this->considerInheritance) {
            if($ignoreInheritance) {
                $postfix = "." . self::ES_MAPPING_PROPERTY_NOT_INHERITED;
            } else {
                $postfix = "." . self::ES_MAPPING_PROPERTY_STANDARD;
            }
        }

        return $postfix;
    }


    /**
     * @param $fieldFilter
     *
     * filter field format as follows:
     *   - simple string like
     *       "filter for value"  --> creates QueryStringQuery
     *
     * @param bool $ignoreInheritance
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $ignoreInheritance = false, $path = "") {
        return new QueryStringQuery($fieldFilter, ["fields" => [$path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance)]]);
    }

    /**
     * @inheritdoc
     */
    public function getExistsFilter($fieldFilter, $ignoreInheritance = false, $path = "")
    {
        return new ExistsQuery($path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance));
    }

    /**
     * @inheritdoc
     */
    public function getFieldSelectionInformation()
    {
        return [new FieldSelectionInformation(
            $this->fieldDefinition->getName(),
            $this->fieldDefinition->getTitle(),
            $this->fieldType,
            [
                'operators' => [BoolQuery::MUST, BoolQuery::SHOULD, BoolQuery::MUST_NOT, FilterEntry::EXISTS, FilterEntry::NOT_EXISTS],
                'classInheritanceEnabled' => $this->considerInheritance
            ]
        )];
    }
}
