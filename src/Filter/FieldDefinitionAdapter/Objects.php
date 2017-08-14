<?php

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;

use ESBackendSearch\FieldSelectionInformation;
use ESBackendSearch\FilterEntry;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use Pimcore\Model\Object\ClassDefinition\Data;

class Objects extends Href implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "objects";


    /**
     * returns selectable fields with their type information for search frontend
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformation()
    {
        $allowedTypes = [];
        $allowedClasses = [];
        $allowedTypes[] = ["objects", "object_ids"];
        $allowedTypes[] = ["object_filter", "object_filter"];

        foreach($this->fieldDefinition->getClasses() as $class) {
            $allowedClasses[] = $class['classes'];
        }

        return [new FieldSelectionInformation(
            $this->fieldDefinition->getName(),
            $this->fieldDefinition->getTitle(),
            $this->fieldType,
            [
                'operators' => [BoolQuery::MUST, BoolQuery::SHOULD, BoolQuery::MUST_NOT, FilterEntry::EXISTS, FilterEntry::NOT_EXISTS],
                'allowedTypes' => $allowedTypes,
                'allowedClasses' => $allowedClasses
            ]
        )];
    }

}
