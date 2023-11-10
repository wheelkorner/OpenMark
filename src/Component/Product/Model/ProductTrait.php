<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Product\Model;

use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

trait ProductTrait
{
    protected ?VendorInterface $vendor = null;

    protected bool $deleted = false;

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function resetImages(): void
    {
        $this->images = new ArrayCollection();
    }

    public function hasVendor(): bool
    {
        return isset($this->vendor);
    }

    public function getVendor(): ?VendorInterface
    {
        return $this->vendor;
    }

    public function setVendor(?VendorInterface $vendor): void
    {
        $this->vendor = $vendor;
    }

    public function setAttributesFrom(DraftInterface $draft): void
    {
        $this->attributes = $draft->getAttributes();
    }

    public function setChannels(Collection $channels): void
    {
        $this->channels = $channels;
    }
}
