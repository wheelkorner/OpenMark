<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Product\Factory;

use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftAttributeInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use Sylius\Component\Product\Model\ProductAttribute;
use Sylius\Component\Product\Model\ProductAttributeInterface;

final class ProductAttributeFactory implements ProductAttributeFactoryInterface
{
    public function createClone(DraftAttributeInterface $draftAttribute): ProductAttributeInterface
    {
        $productAttribute = new ProductAttribute();
        $productAttribute->setTranslatable($draftAttribute->isTranslatable());
        $productAttribute->setStorageType($draftAttribute->getStorageType());
        $productAttribute->setConfiguration($draftAttribute->getConfiguration());
        /** @var VendorInterface $vendor */
        $vendor = $draftAttribute->getVendor();
        $vendorID = $vendor->getId();
        $productAttribute->setCode($draftAttribute->getCode() . '-' . $vendorID);
        $productAttribute->setType($draftAttribute->getType());
        $productAttribute->setPosition($draftAttribute->getPosition());

        return $productAttribute;
    }
}
