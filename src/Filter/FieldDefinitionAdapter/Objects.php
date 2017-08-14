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
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;

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
