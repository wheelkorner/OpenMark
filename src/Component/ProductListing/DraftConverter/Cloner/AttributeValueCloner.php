<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\ProductListing\DraftConverter\Cloner;

use BitBag\OpenMarketplace\Component\Product\Entity\ProductInterface;
use BitBag\OpenMarketplace\Component\Product\Factory\ProductAttributeValueFactoryInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftAttributeInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use Doctrine\ORM\EntityManagerInterface;

final class AttributeValueCloner implements AttributeValueClonerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductAttributeValueFactoryInterface $attributeValueFactory
    ) {
    }

    public function clone(DraftInterface $productDraft, ProductInterface $product): void
    {
        $attributeValues = $productDraft->getAttributes();
        foreach ($attributeValues as $draftAttributeValue) {
            /** @var DraftAttributeInterface $draftAttribute */
            $draftAttribute = $draftAttributeValue->getAttribute();
            $productAttribute = $draftAttribute->getProductAttribute();
            $newProductAttributeValue = $this->attributeValueFactory->create();
            $newProductAttributeValue->setSubject($product);
            $newProductAttributeValue->setAttribute($productAttribute);
            $newProductAttributeValue->setLocaleCode($draftAttributeValue->getLocaleCode());
            $newProductAttributeValue->setValue($draftAttributeValue->getValue());
            $this->entityManager->persist($newProductAttributeValue);
        }
    }
}
