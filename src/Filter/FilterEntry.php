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


namespace AdvancedObjectSearchBundle\Filter;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;

class FilterEntry {

    const EXISTS = "exists";
    const NOT_EXISTS = "not_exists";

    const FIELDNAME_GROUP = "~~group~~";

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
     * defines if inheritance should be considered or not during filtering
     *
     * @var bool
     */
    protected $ignoreInheritance;

    /**
     * FilterEntry constructor.
     * @param string $fieldname
     * @param BuilderInterface|string|\stdClass|array $filterEntryData
     * @param string $operator
     * @param bool $ignoreInheritance
     */
    public function __construct($fieldname, $filterEntryData, $operator = BoolQuery::MUST, $ignoreInheritance = false)
    {
        if($operator) {
            $this->operator = $operator;
        }
        $this->fieldname = $fieldname;
        $this->filterEntryData = $filterEntryData;
        $this->ignoreInheritance = $ignoreInheritance;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    public function getOuterOperator() {
        if($this->operator == self::EXISTS) {
            return BoolQuery::MUST;
        } else if($this->operator == self::NOT_EXISTS) {
            return BoolQuery::MUST_NOT;
        } else {
            return $this->operator;
        }
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
     * @return \stdClass | BuilderInterface | string | array
     */
    public function getFilterEntryData()
    {
        return $this->filterEntryData;
    }

    /**
     * @param \stdClass | BuilderInterface | string | array $filterEntryData
     */
    public function setFilterEntryData($filterEntryData)
    {
        $this->filterEntryData = $filterEntryData;
    }

    /**
     * @return bool
     */
    public function isGroup() {
        return $this->fieldname == self::FIELDNAME_GROUP;
    }

    /**
     * @return bool
     */
    public function getIgnoreInheritance()
    {
        return $this->ignoreInheritance;
    }

}
