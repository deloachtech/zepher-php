<?php
/**
 * This file is part of the deloachtech/zepher-php package.
 *
 * (c) DeLoach Tech, LLC
 * https://deloachtech.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DeLoachTech\Zepher;

class AccessValueObject
{
    private $accountId;
    private $domainId;
    private $versionId;
    private $activated;
    private $lastProcess;
    private $closed;

    /**
     * @param $accountId
     */
    public function __construct($accountId)
    {
        $this->accountId = $accountId;
    }

    public function getAccountId()
    {
        return $this->accountId;
    }

    public function getDomainId()
    {
        return $this->domainId;
    }

    public function setDomainId(?string $domainId): self
    {
        $this->domainId = $domainId;
        return $this;
    }

    public function getVersionId()
    {
        return $this->versionId;
    }

    public function setVersionId(?string $versionId): self
    {
        $this->versionId = $versionId;
        return $this;
    }

    public function getActivated(): ?int
    {
        return $this->activated;
    }

    public function setActivated(?int $timestamp): self
    {
        $this->activated = $timestamp;
        return $this;
    }

    public function getLastProcess(): ?int
    {
        return $this->lastProcess;
    }

    public function setLastProcess(?int $timestamp): self
    {
        $this->lastProcess = $timestamp;
        return $this;
    }

    public function getClosed(): ?int
    {
        return $this->closed;
    }

    public function setClosed(?int $timestamp): self
    {
        $this->closed = $timestamp;
        return $this;
    }


}