<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace spec\BitBag\OpenMarketplace\Component\ProductListing\DraftConverter\Operator;

use BitBag\OpenMarketplace\Component\Product\Entity\ProductInterface;
use BitBag\OpenMarketplace\Component\Product\Factory\ProductAttributeFactoryInterface;
use BitBag\OpenMarketplace\Component\ProductListing\DraftConverter\Cloner\AttributeTranslationClonerInterface;
use BitBag\OpenMarketplace\Component\ProductListing\DraftConverter\Cloner\AttributeValueClonerInterface;
use BitBag\OpenMarketplace\Component\ProductListing\DraftConverter\Extractor\DraftAttributesExtractorInterface;
use BitBag\OpenMarketplace\Component\ProductListing\DraftConverter\Operator\AttributesOperator;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftAttributeInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftAttributeValueInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Product\Model\ProductAttributeInterface;

final class AttributesOperatorSpec extends ObjectBehavior
{
    public function let(
        ProductAttributeFactoryInterface $productAttributeFactory,
        EntityManagerInterface $entityManager,
        DraftAttributesExtractorInterface $attributesExtractor,
        AttributeTranslationClonerInterface $attributeTranslationCloner,
        AttributeValueClonerInterface $attributeValueCloner
    ): void {
        $this->beConstructedWith(
            $productAttributeFactory,
            $entityManager,
            $attributesExtractor,
            $attributeTranslationCloner,
            $attributeValueCloner
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(AttributesOperator::class);
    }

    public function it_doesnt_link_draft_to_product_attribute_when_it_is_already_linked(
        DraftInterface $productDraft,
        AttributeValueClonerInterface $attributeValueCloner,
        EntityManagerInterface $entityManager,
        DraftAttributeInterface $draftAttribute,
        DraftAttributeValueInterface $draftAttributeValue,
        ProductInterface $product,
        ProductAttributeInterface $productAttribute,
        DraftAttributesExtractorInterface $attributesExtractor,
        ) {
        $draftAttributesCollection = new ArrayCollection([$draftAttributeValue->getWrappedObject()]);
        $productDraft->getAttributes()->willReturn($draftAttributesCollection);

        $product->getAttributes()->willReturn(new ArrayCollection([]));

        $attributesExtractor->extract($draftAttributesCollection)->willReturn([$draftAttribute]);

        $draftAttribute->getProductAttribute()->willReturn($productAttribute);

        $this->convert($productDraft, $product);

        $entityManager->persist(Argument::any())->shouldNotHaveBeenCalled();

        $attributeValueCloner->clone($productDraft, $product)->shouldHaveBeenCalledOnce();
    }

    public function it_links_draft_with_product_attribute(
        DraftInterface $productDraft,
        ProductAttributeFactoryInterface $productAttributeFactory,
        AttributeValueClonerInterface $attributeValueCloner,
        EntityManagerInterface $entityManager,
        DraftAttributeInterface $draftAttribute,
        DraftAttributeValueInterface $draftAttributeValue,
        ProductInterface $product,
        ProductAttributeInterface $productAttribute,
        ProductAttributeInterface $newProductAttribute,
        DraftAttributesExtractorInterface $attributesExtractor,
        AttributeTranslationClonerInterface $attributeTranslationCloner
    ) {
        $draftAttributesCollection = new ArrayCollection([$draftAttributeValue->getWrappedObject()]);
        $productDraft->getAttributes()->willReturn($draftAttributesCollection);

        $product->getAttributes()->willReturn(new ArrayCollection([]));

        $attributesExtractor->extract($draftAttributesCollection)->willReturn([$draftAttribute]);

        $draftAttribute->getProductAttribute()->willReturn(null);

        $productAttributeFactory->createClone($draftAttribute)->willReturn($newProductAttribute);

        $draftAttribute->setProductAttribute($newProductAttribute)->shouldBeCalledOnce();
        $entityManager->persist($newProductAttribute)->shouldBeCalledOnce();
        $entityManager->flush()->shouldBeCalledOnce();
        $attributeTranslationCloner->clone($draftAttribute)->shouldBeCalledOnce();

        $this->convert($productDraft, $product);

        $attributeValueCloner->clone($productDraft, $product)->shouldHaveBeenCalledOnce();
    }
}
