<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Order\Entity;

use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;

trait ShipmentTrait
{
    private ?VendorInterface $vendor = null;

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
}
