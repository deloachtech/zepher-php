<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AccessRepository;

/**
 * @ORM\Entity(repositoryClass=AccessRepository::class)
 */
class Access
{

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="string", options={"collation":"utf8_bin"}, nullable=false, length=20)
     */
    private $accountId;

    public function setAccountId($accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }


    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="string", options={"collation":"utf8_bin"}, nullable=false, length=20)
     */
    private $versionId;

    public function setVersionId(string $versionId): self
    {
        $this->versionId = $versionId;
        return $this;
    }


    public function getVersionId(): string
    {
        return $this->versionId;
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="integer", nullable=false, options={"unsigned": true}, length=14)
     */
    private $activated;

    public function setActivated(int $activated): self
    {
        $this->activated = $activated;
        return $this;
    }


    public function getActivated(): int
    {
        return $this->activated;
    }


    /**
     * @ORM\Column(type="integer", nullable=true, options={"unsigned": true},  length=14)
     */
    private $lastProcess;

    public function setLastProcess(?int $lastProcess): self
    {
        $this->lastProcess = $lastProcess;
        return $this;
    }


    public function getLastProcess(): ?int
    {
        return $this->lastProcess;
    }


    /**
     * @ORM\Column(type="integer", nullable=true, options={"unsigned": true},  length=14)
     */
    private $closed;

    public function setClosed(?int $closed): self
    {
        $this->closed = $closed;
        return $this;
    }


    public function getClosed(): ?int
    {
        return $this->closed;
    }
}