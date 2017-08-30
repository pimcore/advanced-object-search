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
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;

class Href extends DefaultAdapter implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "href";

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
                            'type' => 'nested',
                            'properties' => [
                                'type' =>  ["type" => "string", "index" => "not_analyzed"],
                                'subtype' =>  ["type" => "string", "index" => "not_analyzed"],
                                'id' => ["type" => "long"]
                            ]
                        ],
                        self::ES_MAPPING_PROPERTY_NOT_INHERITED => [
                            'type' => 'nested',
                            'properties' => [
                                'type' =>  ["type" => "string", "index" => "not_analyzed"],
                                'subtype' =>  ["type" => "string", "index" => "not_analyzed"],
                                'id' => ["type" => "long"]
                            ]
                        ]
                    ]
                ],
            ];
        } else {
            return [
                $this->fieldDefinition->getName(),
                [
                    'type' => 'nested',
                    'properties' => [
                        'type' =>  ["type" => "string", "index" => "not_analyzed"],
                        'subtype' =>  ["type" => "string", "index" => "not_analyzed"],
                        'id' => ["type" => "long"]
                    ]
                ]
            ];
        }

    }


    /**
     * @param $fieldFilter
     *
     * filter field format as follows:
     *   - simple array like
     *      [
     *          'type' => 'object|asset|document'
     *          'id'   => 3242  ( or id-array like [3234,2432,24342,35435]
     *      ]
     *      --> creates TermQuery
     *
     *   - array with sub query
     *      [
     *         'type'               => 'object|asset|document'
     *         'classId'            => 'CLASSID' (optional only with type object),
     *         'fulltextSearchTerm' => string as fulltext term
     *         'filters'            => [ STANDARD FULL FEATURED FILTER ARRAY ]
     *      ]
     *       --> creates a sub query with given information, receives ids and then creates TermsQuery
     *
     * @param bool $ignoreInheritance
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $ignoreInheritance = false, $path = "")
    {
        if(is_array($fieldFilter)) {

            $path = $path . $this->fieldDefinition->getName() . $this->buildQueryFieldPostfix($ignoreInheritance);

            $boolQuery = new BoolQuery();

            if($fieldFilter['id']) {

                $idArray = $fieldFilter['id'];
                if(!is_array($idArray)) {
                    $idArray = [$idArray];
                } else {
                    $idArray = array_filter($idArray);
                }

            } else if($fieldFilter['type'] == "object" && $fieldFilter['classId'] && ($fieldFilter['filters'] || $fieldFilter['fulltextSearchTerm'])) {

                $results = $this->service->doFilter($fieldFilter['classId'], $fieldFilter['filters'], $fieldFilter['fulltextSearchTerm']);
                $idArray = $this->service->extractIdsFromResult($results);


            } else {
                throw new \Exception("invalid filter entry definition " . print_r($fieldFilter, true));
            }

            if($idArray) {
                foreach($idArray as $id) {
                    $innerBoolQuery = new BoolQuery();
                    $innerBoolQuery->add(new TermQuery($path . ".type", $fieldFilter['type']));
                    $innerBoolQuery->add(new TermQuery($path . ".id", $id));

                    $boolQuery->add(new NestedQuery($path, $innerBoolQuery));
                }
            } else {
                $boolQuery->add(new ExistsQuery($path . ".notavailablefield"));
            }

            return $boolQuery;
        } else {
            throw new \Exception("invalid filter entry for relations filter: " . print_r($fieldFilter, true));
        }
    }


    /**
     * returns selectable fields with their type information for search frontend
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformation()
    {
        $allowedTypes = [];
        $allowedClasses = [];
        if($this->fieldDefinition->getAssetsAllowed()) {
            $allowedTypes[] = ["asset", "asset_ids"];
        }
        if($this->fieldDefinition->getDocumentsAllowed()) {
            $allowedTypes[] = ["document", "document_ids"];
        }
        if($this->fieldDefinition->getObjectsAllowed()) {
            $allowedTypes[] = ["object", "object_ids"];
            $allowedTypes[] = ["object_filter", "object_filter"];

            foreach($this->fieldDefinition->getClasses() as $class) {
                $allowedClasses[] = $class['classes'];
            }
        }

        return [new FieldSelectionInformation(
            $this->fieldDefinition->getName(),
            $this->fieldDefinition->getTitle(),
            $this->fieldType,
            [
                'operators' => [BoolQuery::MUST, BoolQuery::SHOULD, BoolQuery::MUST_NOT, FilterEntry::EXISTS, FilterEntry::NOT_EXISTS],
                'allowedTypes' => $allowedTypes,
                'allowedClasses' => $allowedClasses,
                'classInheritanceEnabled' => $this->considerInheritance
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

        if($ignoreInheritance) {
            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }

        return $value;
    }
}
