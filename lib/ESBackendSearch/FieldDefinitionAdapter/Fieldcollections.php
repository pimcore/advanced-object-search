<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Object\Fieldcollection;

class Fieldcollections extends DefaultAdapter implements IFieldDefinitionAdapter {

    /**
     * @var Data\Fieldcollections
     */
    protected $fieldDefinition;

    /**
     * @return array
     */
    public function getESMapping() {

        $allowedTypes = $this->fieldDefinition->getAllowedTypes();
        if(empty($allowedTypes)) {
            $allFieldCollectionTypes = new Fieldcollection\Definition\Listing();
            foreach($allFieldCollectionTypes->load() as $type) {
                $allowedTypes[] = $type->getKey();
            }
        }

        $mappingProperties = [];

        foreach($allowedTypes as $fieldCollectionKey) {
            /**
             * @var $fieldCollectionDefinition Fieldcollection\Definition
             */
            $fieldCollectionDefinition = Fieldcollection\Definition::getByKey($fieldCollectionKey);

            $childMappingProperties = [];
            foreach($fieldCollectionDefinition->getFieldDefinitions() as $field) {
                $fieldDefinitionAdapter = $this->service->getFieldDefinitionAdapter($field);
                list($key, $mappingEntry) = $fieldDefinitionAdapter->getESMapping();
                $childMappingProperties[$key] = $mappingEntry;
            }

            $mappingProperties[$fieldCollectionKey] = [
                'type' => 'nested',
                'properties' => $childMappingProperties
            ];

        }

        return [
            $this->fieldDefinition->getName(),
            [
                'type' => 'nested',
                'properties' => $mappingProperties
            ]
        ];
    }


    /**
     * @param Concrete $object
     * @return array
     */
    public function getIndexData($object) {

        $data = [];

        $getter = "get" . ucfirst($this->fieldDefinition->getName());
        $fieldCollectionItems = $object->$getter();

        if($fieldCollectionItems) {
            foreach($fieldCollectionItems->getItems() as $item) {
                /**
                 * @var $item \Pimcore\Model\Object\Fieldcollection\Data\AbstractData
                 */
                $definition = Fieldcollection\Definition::getByKey($item->getType());

                $fieldCollectionData = [];

                foreach($definition->getFieldDefinitions() as $key => $field) {
                    $fieldDefinitionAdapter = $this->service->getFieldDefinitionAdapter($field);
                    $fieldCollectionData[$key] = $fieldDefinitionAdapter->getIndexData($item);
                }

                $data[$item->getType()][] = $fieldCollectionData;


            }
        }

        return $data;
    }

}