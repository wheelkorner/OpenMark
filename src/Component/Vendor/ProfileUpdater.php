<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Vendor;

use BitBag\OpenMarketplace\Component\Vendor\Entity\BackgroundImageInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\LogoImageInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\ProfileInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\ProfileUpdate\ProfileUpdateInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use BitBag\OpenMarketplace\Component\Vendor\Profile\BackgroundImageOperatorInterface;
use BitBag\OpenMarketplace\Component\Vendor\Profile\Factory\ProfileUpdateBackgroundImageFactoryInterface;
use BitBag\OpenMarketplace\Component\Vendor\Profile\Factory\ProfileUpdateFactoryInterface;
use BitBag\OpenMarketplace\Component\Vendor\Profile\Factory\ProfileUpdateLogoImageFactoryInterface;
use BitBag\OpenMarketplace\Component\Vendor\Profile\LogoImageOperatorInterface;
use BitBag\OpenMarketplace\Component\Vendor\Profile\ProfileUpdateRemoverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Uploader\ImageUploader;
use Sylius\Component\Mailer\Sender\SenderInterface;

final class ProfileUpdater implements ProfileUpdaterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SenderInterface $sender,
        private ProfileUpdateRemoverInterface $remover,
        private ProfileUpdateFactoryInterface $profileUpdateFactory,
        private ProfileUpdateLogoImageFactoryInterface $imageFactory,
        private ProfileUpdateBackgroundImageFactoryInterface $backgroundImageFactory,
        private ImageUploader $imageUploader,
        private LogoImageOperatorInterface $vendorLogoOperator,
        private BackgroundImageOperatorInterface $vendorBackgroundImageOperator
    ) {
    }

    public function createPendingVendorProfileUpdate(
        ProfileInterface $vendorData,
        VendorInterface $currentVendor,
        ?LogoImageInterface $image,
        ?BackgroundImageInterface $backgroundImage
    ): void {
        $pendingVendorUpdate = $this->profileUpdateFactory->createWithGeneratedTokenAndVendor($currentVendor);

        if ($image && $image->getFile()) {
            $imageEntity = $this->imageFactory->createWithFileAndOwner($image, $pendingVendorUpdate);

            $this->imageUploader->upload($imageEntity);
            $pendingVendorUpdate->setImage($imageEntity);
            $this->entityManager->persist($imageEntity);
        }

        if ($image && !$image->getPath()) {
            $currentVendor->setImage(null);
        }

        if ($backgroundImage && $backgroundImage->getFile()) {
            $backgroundImageEntity = $this->backgroundImageFactory->createWithFileAndOwner($backgroundImage, $pendingVendorUpdate);

            $this->imageUploader->upload($backgroundImageEntity);
            $pendingVendorUpdate->setBackgroundImage($backgroundImageEntity);
            $this->entityManager->persist($backgroundImageEntity);
        }

        if ($backgroundImage && !$backgroundImage->getPath()) {
            $currentVendor->setBackgroundImage(null);
        }

        $this->entityManager->persist($pendingVendorUpdate);

        $token = $pendingVendorUpdate->getToken();

        $this->setVendorFromData($pendingVendorUpdate, $vendorData);

        $this->entityManager->flush();
        $shopUser = $currentVendor->getShopUser();
        $email = $shopUser->getEmail();

        $this->sender->send('vendor_profile_update', [$email], ['token' => $token]);
    }

    public function setVendorFromData(
        ProfileInterface $vendor,
        ProfileInterface $data
    ): void {
        $vendor->setCompanyName($data->getCompanyName());
        $vendor->setTaxIdentifier($data->getTaxIdentifier());
        $vendor->setPhoneNumber($data->getPhoneNumber());
        $vendor->setDescription($data->getDescription());

        $newVendorAddress = $data->getVendorAddress();

        if (null === $newVendorAddress) {
            return;
        }

        if (null !== $vendor->getVendorAddress()) {
            $vendor->getVendorAddress()->setCity($newVendorAddress->getCity());
            $vendor->getVendorAddress()->setCountry($newVendorAddress->getCountry());
            $vendor->getVendorAddress()->setPostalCode($newVendorAddress->getPostalCode());
            $vendor->getVendorAddress()->setStreet($newVendorAddress->getStreet());
        }

        $this->entityManager->persist($vendor);
        $this->entityManager->flush();
    }

    public function updateVendorFromPendingData(ProfileUpdateInterface $vendorData): void
    {
        $vendor = $vendorData->getVendor();

        $this->setVendorFromData($vendor, $vendorData);

        if (null !== $vendorData->getBackgroundImage()) {
            $this->vendorBackgroundImageOperator->replaceVendorImage($vendorData, $vendor);
        }
        if (null !== $vendorData->getImage()) {
            $this->vendorLogoOperator->replaceVendorImage($vendorData, $vendor);
        }

        $this->remover->removePendingUpdate($vendorData);
    }
}
