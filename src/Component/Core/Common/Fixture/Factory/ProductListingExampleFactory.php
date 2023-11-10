<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Common\Fixture\Factory;

use BitBag\OpenMarketplace\Component\Core\Common\StateMachine\ProductDraftStateMachineTransitionInterface;
use BitBag\OpenMarketplace\Component\ProductListing\DraftGenerator\Factory\DraftImageFactoryInterface;
use BitBag\OpenMarketplace\Component\ProductListing\DraftTransitions;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftAttributeInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftAttributeValue;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftTaxon;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftTranslationInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\ListingPriceInterface;
use BitBag\OpenMarketplace\Component\ProductListing\ListingPersisterInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\ShopUserInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use Faker\Factory;
use Faker\Generator;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Webmozart\Assert\Assert;

final class ProductListingExampleFactory implements ExampleFactoryInterface
{
    private Generator $faker;

    public function __construct(
        private FactoryInterface $productDraftFactory,
        private FactoryInterface $productListingPriceFactory,
        private ListingPersisterInterface $listingPersister,
        private FactoryInterface $productTranslationFactory,
        private EntityRepository $shopUserRepository,
        private ChannelRepositoryInterface $channelRepository,
        private RepositoryInterface $localeRepository,
        private RepositoryInterface $draftAttributeRepository,
        private RepositoryInterface $taxonRepository,
        private ProductDraftStateMachineTransitionInterface $productDraftStateMachineTransition,
        private SlugGeneratorInterface $slugGenerator,
        private ImageUploaderInterface $imageUploader,
        private DraftImageFactoryInterface $draftImageFactory,
        private FileLocatorInterface $fileLocator,
        ) {
        $this->faker = Factory::create();
    }

    public function create(array $options = []): DraftInterface
    {
        /** @var DraftInterface $productDraft */
        $productDraft = $this->productDraftFactory->createNew();

        $productDraft->setCode($options['code']);

        /** @var ShopUserInterface $shopUser */
        $shopUser = $this->shopUserRepository->findOneBy(['username' => $options['vendor']]);
        Assert::notNull($shopUser);

        /** @var VendorInterface $vendor */
        $vendor = $shopUser->getVendor();
        Assert::notNull($vendor);

        $this->listingPersister->createNewProductListing($productDraft, $vendor);

        /** @var ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            $code = $channel->getCode();
            if (null === $code) {
                continue;
            }

            $productDraft->addChannel($channel);
            $this->createProductListingPricing($productDraft, $code);
        }

        $this->createTranslations($productDraft, $options);
        $this->createAttributes($productDraft, $vendor, $options);
        $this->createRandomImage($productDraft, $options);
        $this->attachToTaxons($productDraft, $options);

        $this->productDraftStateMachineTransition->applyIfCan($productDraft, DraftTransitions::TRANSITION_SEND_TO_VERIFICATION);
        $this->productDraftStateMachineTransition->applyIfCan($productDraft, DraftTransitions::TRANSITION_ACCEPT);

        return $productDraft;
    }

    private function createProductListingPricing(DraftInterface $productDraft, string $channelCode): void
    {
        /** @var ListingPriceInterface $productListingPrice */
        $productListingPrice = $this->productListingPriceFactory->createNew();
        $productListingPrice->setChannelCode($channelCode);
        $productListingPrice->setPrice($this->faker->numberBetween(100, 10000));
        $productListingPrice->setOriginalPrice($this->faker->numberBetween(100, 10000));
        $productListingPrice->setMinimumPrice(0);
        $productListingPrice->setProductDraft($productDraft);

        $productDraft->addProductListingPrice($productListingPrice);
    }

    private function createTranslations(DraftInterface $productDraft, array $options): void
    {
        foreach ($this->getLocales() as $localeCode) {
            /** @var DraftTranslationInterface $productDraftTranslation */
            $productDraftTranslation = $this->productTranslationFactory->createNew();
            $productDraftTranslation->setLocale($localeCode);
            $productDraftTranslation->setName($options['name']);
            $productDraftTranslation->setSlug($this->slugGenerator->generate($options['name']));

            /** @var string $description */
            $description = $this->faker->paragraphs(3, true);
            $productDraftTranslation->setDescription($description);

            /** @var string $shortDescription */
            $shortDescription = $this->faker->paragraphs(1, true);
            $shortDescription = substr($shortDescription, 0, 254) . '.';
            $productDraftTranslation->setShortDescription($shortDescription);
            $productDraftTranslation->setMetaDescription(null);
            $productDraftTranslation->setMetaKeywords(null);
            $productDraftTranslation->setProductDraft($productDraft);
            $productDraft->addTranslation($productDraftTranslation);
        }
    }

    private function createAttributes(
        DraftInterface $productDraft,
        VendorInterface $vendor,
        array $options
    ): void {
        if (!isset($options['attributes'])) {
            return;
        }

        foreach ($options['attributes'] as $attributeData) {
            /** @var DraftAttributeInterface $attribute */
            $attribute = $this->draftAttributeRepository->findOneBy([
                'code' => $attributeData['code'],
                'vendor' => $vendor->getId(),
            ]);

            $attributeValue = new DraftAttributeValue();
            $attributeValue->setAttribute($attribute);
            $attributeValue->setSubject($productDraft);
            $attributeValue->setValue($attributeData['value']);

            $productDraft->addAttribute($attributeValue);
        }
    }

    private function getLocales(): iterable
    {
        /** @var LocaleInterface[] $locales */
        $locales = $this->localeRepository->findAll();
        foreach ($locales as $locale) {
            yield $locale->getCode();
        }
    }

    private function createRandomImage(DraftInterface $product, array $options): void
    {
        if (!count($options['images'])) {
            return;
        }

        $i = 0;
        foreach ($options['images'] as $imagePath) {
            $imageType = 0 === $i ? 'main' : '';

            /** @var string $imagePath */
            $imagePath = $this->fileLocator->locate($imagePath);
            $uploadedImage = new UploadedFile($imagePath, basename($imagePath));

            /** @var ImageInterface $productImage */
            $productImage = $this->draftImageFactory->createNew();
            $productImage->setFile($uploadedImage);
            $productImage->setType($imageType);

            $this->imageUploader->upload($productImage);

            $product->addImage($productImage);
            $productImage->setOwner($product);

            ++$i;
        }
    }

    private function attachToTaxons(DraftInterface $productDraft, array $options): void
    {
        if (isset($options['main_taxon'])) {
            /** @var TaxonInterface $taxon */
            $taxon = $this->taxonRepository->findOneBy(['code' => $options['main_taxon']]);
            $productDraft->setMainTaxon($taxon);
        }

        if (!isset($options['taxons'])) {
            return;
        }

        foreach ($options['taxons'] as $taxonCode) {
            /** @var TaxonInterface $taxon */
            $taxon = $this->taxonRepository->findOneBy(['code' => $taxonCode]);

            $productDraftTaxon = new DraftTaxon();
            $productDraftTaxon->setProductDraft($productDraft);
            $productDraftTaxon->setTaxon($taxon);

            $productDraft->addProductDraftTaxon($productDraftTaxon);
        }
    }
}
