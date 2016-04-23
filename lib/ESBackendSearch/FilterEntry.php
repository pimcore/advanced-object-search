<?php

namespace ESBackendSearch;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\BoolQuery;

class FilterEntry {

    /**
     * operator for combining filters, default = MUST
     *
     * @var string
     */
    protected $operator = BoolQuery::MUST;

    /**
     * fieldname to filter
     *
     * @var string
     */
    protected $fieldname;

    /**
     * filter entry data
     * can be instance of BuilderInterface for adapter specific stdClass - FieldDefinitionAdapters for details
     *
     * @var BuilderInterface | \stdClass | string
     */
    protected $filterEntryData;

    /**
     * FilterEntry constructor.
     * @param string $fieldname
     * @param BuilderInterface|string|\stdClass $filterEntryData
     * @param string $operator
     */
    public function __construct($fieldname, $filterEntryData, $operator = BoolQuery::MUST)
    {
        if($operator) {
            $this->operator = $operator;
        }
        $this->fieldname = $fieldname;
        $this->filterEntryData = $filterEntryData;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @param string $fieldname
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;
    }

    /**
     * @return \stdClass | BuilderInterface | string
     */
    public function getFilterEntryData()
    {
        return $this->filterEntryData;
    }

    /**
     * @param \stdClass | BuilderInterface | string $filterEntryData
     */
    public function setFilterEntryData($filterEntryData)
    {
        $this->filterEntryData = $filterEntryData;
    }

}