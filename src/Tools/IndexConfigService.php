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

namespace AdvancedObjectSearchBundle\Tools;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class IndexConfigService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    protected $indexNamePrefix;

    /**
     * @var array
     */
    protected $indexConfiguration;

    /**
     * IndexConfigService constructor.
     *
     * @param string $indexNamePrefix
     * @param array $indexConfiguration
     */
    public function __construct(string $indexNamePrefix, array $indexConfiguration)
    {
        $this->indexNamePrefix = $indexNamePrefix;
        $this->indexConfiguration = $indexConfiguration;
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
     * @param string $key
     *
     * @return mixed
     */
    public function getIndexConfiguration(string $key)
    {
        return $this->indexConfiguration[$key] ?? null;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
