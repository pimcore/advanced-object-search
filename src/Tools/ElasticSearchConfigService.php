<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace AdvancedObjectSearchBundle\Tools;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class ElasticSearchConfigService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string[]
     */
    protected $hosts;
    /**
     * @var string
     */
    protected $indexNamePrefix;

    /**
     * ElasticSearchConfigService constructor.
     *
     * @param string[] $hosts
     * @param string $indexNamePrefix
     */
    public function __construct(array $hosts, string $indexNamePrefix)
    {
        $this->hosts = $hosts;
        $this->indexNamePrefix = $indexNamePrefix;
    }

    /**
     * @return string[]
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    /**
     * @param string[] $hosts
     */
    public function setHosts(array $hosts): void
    {
        $this->hosts = $hosts;
    }

    /**
     * @return string
     */
    public function getIndexNamePrefix(): string
    {
        return $this->indexNamePrefix;
    }

    /**
     * @param string $indexNamePrefix
     */
    public function setIndexNamePrefix(string $indexNamePrefix): void
    {
        $this->indexNamePrefix = $indexNamePrefix;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
