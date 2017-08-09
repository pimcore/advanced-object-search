<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use ESBackendSearch\FieldSelectionInformation;
use ESBackendSearch\FilterEntry;
use ESBackendSearch\Service;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\QueryStringQuery;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;

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
     * @var ClassDefinition
     */
    protected $classDefinition;

    /**
     * @var Service
     */
    protected $service;

    /**
     * DefaultAdapter constructor.
     * @param Data $fieldDefinition
     * @param Service $service
     * @param ClassDefinition $classDefinition
     */
    public function __construct(Data $fieldDefinition, Service $service, ClassDefinition $classDefinition) {
        $this->fieldDefinition = $fieldDefinition;
        $this->service = $service;
        $this->classDefinition = $classDefinition;
    }

    /**
     * @return array
     */
    public function getESMapping() {

        if($this->classDefinition->getAllowInherit()) {
            return [
                $this->fieldDefinition->getName(),
                [
                    'properties' => [
                        'standard' => [
                            'type' => 'string',
                            'fields' => [
                                "raw" =>  [ "type" => "string", "index" => "not_analyzed" ]
                            ]
                        ],
                        'notInherited' => [
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
     * @return mixed
     */
    public function getIndexData($object) {

        $value = $this->fieldDefinition->getForWebserviceExport($object);

        if($this->classDefinition->getAllowInherit()) {

            $inheritanceBackup = AbstractObject::getGetInheritedValues();
            AbstractObject::setGetInheritedValues(false);
            $notInheritedValue = $this->fieldDefinition->getForWebserviceExport($object);
            AbstractObject::setGetInheritedValues($inheritanceBackup);

            $returnValue = null;
            if($value) {
                $returnValue['standard'] = (string) $value;
            }

            if($notInheritedValue) {
                $returnValue['notInherited'] = (string) $value;
            }

            return $returnValue;

        } else {

            if($value) {
                return (string) $value;
            } else {
                return null;
            }
        }
    }


    protected function buildQueryFieldPostfix($ignoreInheritance = false) {
        $postfix = "";

        if($this->classDefinition->getAllowInherit()) {
            if($ignoreInheritance) {
                $postfix = ".notInherited";
            } else {
                $postfix = ".standard";
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
                'classInheritanceEnabled' => $this->classDefinition->getAllowInherit()
            ]
        )];
    }
}