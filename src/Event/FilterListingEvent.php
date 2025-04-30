<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace AdvancedObjectSearchBundle\Event;

use Pimcore\Model\DataObject\Listing;
use Symfony\Contracts\EventDispatcher\Event;

class FilterListingEvent extends Event
{
    /**
     * @var Listing
     */
    protected $listing;

    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    /**
     * @return Listing
     */
    public function getListing(): Listing
    {
        return $this->listing;
    }
}
