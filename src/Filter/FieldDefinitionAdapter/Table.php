<?php

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;

use AdvancedObjectSearchBundle\Filter\FieldSelectionInformation;
use AdvancedObjectSearchBundle\Filter\FilterEntry;
use AdvancedObjectSearchBundle\Service;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;

class Table implements IFieldDefinitionAdapter
{

    /**
     * @url https://www.elastic.co/guide/en/elasticsearch/reference/current/ignore-above.html
     *
     * The value for ignore_above is the character count, but Lucene counts bytes. If you use UTF-8 text with
     * many non-ASCII characters, you may want to set the limit to 32766 / 4 = 8191 since UTF-8 characters may
     * occupy at most 4 bytes.
     */
    const IGNORE_ABOVE = 8191;

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "table";

    /**
     * @var Data
     */
    protected $fieldDefinition;

    /**
     * @var bool
     */
    protected $considerInheritance;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var Service
     */
    protected $service;

    /**
     * Table constructor.
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @param Data $fieldDefinition
     */
    public function setFieldDefinition(Data $fieldDefinition)
    {
        $this->fieldDefinition = $fieldDefinition;
    }

    /**
     * @param bool $considerInheritance
     */
    public function setConsiderInheritance(bool $considerInheritance)
    {
        $this->considerInheritance = $considerInheritance;
    }

    /**
     * @return array
     */
    public function getESMapping()
    {
        $mapping = [
            'type' => 'keyword'
        ];

        if ($this->isColumnConfigActivated()) {
            $mapping['type'] = 'nested';
            foreach ($this->fieldDefinition->columnConfig as $columnConfig) {
                $mapping['properties'][$columnConfig['key']] = ['type' => 'keyword'];
            }
        } else {
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/ignore-above.html
            $mapping['ignore_above'] = self::IGNORE_ABOVE;
        }

        if ($this->considerInheritance) {
            $inheritanceMapping = [
                $this->fieldDefinition->getName(),
                [
                    'properties' => [
                        self::ES_MAPPING_PROPERTY_STANDARD => $mapping,
                        self::ES_MAPPING_PROPERTY_NOT_INHERITED => $mapping
                    ]
                ]
            ];

            return $inheritanceMapping;
        } else {
            return [
                $this->fieldDefinition->getName(),
                $mapping
            ];
        }
    }

    /**
     * @param $object
     * @param bool $ignoreInheritance
     * @return string
     */
    protected function doGetIndexDataValue($object, $ignoreInheritance = false)
    {
        $inheritanceBackup = null;
        if ($ignoreInheritance) {
            $inheritanceBackup = AbstractObject::getGetInheritedValues();
            AbstractObject::setGetInheritedValues(false);
        }

        $value = $this->fieldDefinition->getForWebserviceExport($object);
        if ($this->isColumnConfigActivated()) {
            // When saving an object the array doesnt have named keys, so first get data for resource
            // and then get the data from resource. This way we have named keys in the data array
            $value = $this->fieldDefinition->getDataFromResource($this->fieldDefinition->getDataForResource($value,
                $object));
        }

        if ($ignoreInheritance) {
            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }

        if (!$this->isColumnConfigActivated() && is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * @param Concrete $object
     * @return mixed
     */
    public function getIndexData($object)
    {

        $value = $this->doGetIndexDataValue($object, false);

        if ($this->considerInheritance) {
            $notInheritedValue = $this->doGetIndexDataValue($object, true);

            $returnValue = null;
            if ($value) {
                $returnValue[self::ES_MAPPING_PROPERTY_STANDARD] = $value;
            }

            if ($notInheritedValue) {
                $returnValue[self::ES_MAPPING_PROPERTY_NOT_INHERITED] = $notInheritedValue;
            }

            return $returnValue;

        } else {

            if ($value) {
                return $value;
            } else {
                return null;
            }
        }
    }


    protected function buildQueryFieldPostfix($ignoreInheritance = false)
    {
        $postfix = "";

        if ($this->considerInheritance) {
            if ($ignoreInheritance) {
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
     * @param bool $ignoreInheritance
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $ignoreInheritance = false, $path = "")
    {
        $term = $fieldFilter['term'];
        $fieldsPath = $path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance);

        // Search in nested field
        if ($this->isColumnConfigActivated()) {
            $boolQuery = new BoolQuery();

            if (!empty($fieldFilter['column'])) {
                $innerBoolQuery = new BoolQuery();
                $innerBoolQuery->add(new TermQuery($fieldsPath . "." . $fieldFilter['column'], $term));
                $boolQuery->add(new NestedQuery($fieldsPath, $innerBoolQuery), BoolQuery::SHOULD);
            } else {
                foreach ($this->fieldDefinition->columnConfig as $column) {
                    $innerBoolQuery = new BoolQuery();
                    $innerBoolQuery->add(new TermQuery($fieldsPath . "." . $column['key'], $term));

                    $boolQuery->add(new NestedQuery($fieldsPath, $innerBoolQuery), BoolQuery::SHOULD);
                }
                $boolQuery->addParameter("minimum_should_match", 1);
            }

            return $boolQuery;
        }

        return new QueryStringQuery($term, ["fields" => [$fieldsPath]]);
    }

    /**
     * @inheritdoc
     */
    public function getFieldSelectionInformation()
    {
        $columnConfig = [];
        if ($this->isColumnConfigActivated()) {
            foreach ($this->fieldDefinition->columnConfig as $column) {
                $columnConfig[] = $column;
            }
        }

        return [
            new FieldSelectionInformation(
                $this->fieldDefinition->getName(),
                $this->fieldDefinition->getTitle(),
                $this->fieldType,
                [
                    'operators' => [
                        BoolQuery::MUST,
                        BoolQuery::SHOULD,
                        BoolQuery::MUST_NOT,
                        FilterEntry::EXISTS,
                        FilterEntry::NOT_EXISTS
                    ],
                    'classInheritanceEnabled' => $this->considerInheritance,
                    'columnConfigActivated' => $this->isColumnConfigActivated(),
                    'columnConfig' => $columnConfig
                ]
            )
        ];
    }

    protected function isColumnConfigActivated()
    {
        return (property_exists($this->fieldDefinition, 'columnConfigActivated')
            && $this->fieldDefinition->columnConfigActivated === true);
    }
}
