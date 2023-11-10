<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace Tests\BitBag\OpenMarketplace\Behat\Context\Vendor;

use Behat\MinkExtension\Context\RawMinkContext;
use BitBag\OpenMarketplace\Component\Vendor\Entity\ProfileUpdate\Address;
use BitBag\OpenMarketplace\Component\Vendor\Entity\ProfileUpdate\ProfileUpdate;
use BitBag\OpenMarketplace\Component\Vendor\Entity\ShopUserInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use BitBag\OpenMarketplace\Component\Vendor\Profile\Factory\LogoImageFactoryInterface;
use Doctrine\Persistence\ObjectManager;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Addressing\Model\Country;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxonomy\Factory\TaxonFactory;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Webmozart\Assert\Assert;

class VendorUpdateContext extends RawMinkContext
{
    private SharedStorageInterface $sharedStorage;

    private UserRepositoryInterface $userRepository;

    private ExampleFactoryInterface $userFactory;

    private ObjectManager $manager;

    private LogoImageFactoryInterface $vendorImageFactory;

    private TaxonFactoryInterface $taxonFactory;

    private ExampleFactoryInterface $vendorExampleFactory;

    private FactoryInterface $countryFactory;

    public function __construct(
        SharedStorageInterface $sharedStorage,
        UserRepositoryInterface $userRepository,
        ExampleFactoryInterface $userFactory,
        ObjectManager $manager,
        LogoImageFactoryInterface $vendorImageFactory,
        TaxonFactory $taxonFactory,
        ExampleFactoryInterface $vendorExampleFactory,
        FactoryInterface $countryFactory,
        ) {
        $this->sharedStorage = $sharedStorage;
        $this->userRepository = $userRepository;
        $this->userFactory = $userFactory;
        $this->manager = $manager;
        $this->vendorImageFactory = $vendorImageFactory;
        $this->taxonFactory = $taxonFactory;
        $this->vendorExampleFactory = $vendorExampleFactory;
        $this->countryFactory = $countryFactory;
    }

    /**
     * @Given there is a :status vendor user :vendor_user_email registered in country :country_code
     */
    public function thereIsAVendorUserRegisteredInCountry(
        $status,
        $vendor_user_email,
        $country_code
    ): void {
        /** @var ShopUserInterface $user */
        $user = $this->userFactory->create(['email' => $vendor_user_email, 'password' => 'password', 'enabled' => true]);
        $user->setVerifiedAt(new \DateTime());
        $user->addRole('ROLE_USER');
        $user->addRole('ROLE_VENDOR');

        $this->sharedStorage->set('user', $user);

        $this->userRepository->add($user);

        $country = $this->manager->getRepository(Country::class)->findOneBy(['code' => $country_code]);
        if (null === $country) {
            /** @var CountryInterface $country */
            $country = $this->countryFactory->createNew();
            $country->setCode($country_code);
            $country->enable();
            $this->manager->persist($country);
        }

        $options = [
            'company_name' => 'Test',
            'phone_number' => '333333333',
            'tax_identifier' => '543455',
            'street' => 'Secret 13',
            'city' => 'Warsaw',
            'postcode' => '00-111',
            'slug' => 'vendor-slug',
            'description' => 'description',
            'country' => $country,
            'status' => $status,
        ];

        $vendor = $this->vendorExampleFactory->create($options);
        $vendor->setShopUser($user);
        $this->manager->persist($vendor);
        $this->manager->flush();
        $this->sharedStorage->set('vendor', $vendor);
    }

    /**
     * @Then Pending update data should appear in database
     */
    public function pendingUpdateDataShouldAppearInDatabase()
    {
        $vendor = $this->sharedStorage->get('vendor');
        $pendingData = $this->manager->getRepository(ProfileUpdate::class)->findOneBy(['vendor' => $vendor]);

        Assert::notEq(null, $pendingData);
    }

    /**
     * @Given There is pending update data with token value :token for logged in vendor
     */
    public function thereIsPendingUpdateDataWithTokenValueForLoggedInVendor($token): void
    {
        $vendor = $this->sharedStorage->get('vendor');
        $country = $this->manager->getRepository(Country::class)->findOneBy(['code' => 'PL']);
        $pendigUpdate = new ProfileUpdate();
        $pendigUpdate->setVendorAddress(new Address());
        $pendigUpdate->setVendor($vendor);
        $pendigUpdate->setToken($token);
        $pendigUpdate->setCompanyName('new Company');
        $pendigUpdate->setTaxIdentifier('new ID');
        $pendigUpdate->setPhoneNumber('new number');
        $pendigUpdate->setDescription('new description');
        $pendigUpdate->getVendorAddress()->setStreet('new street');
        $pendigUpdate->getVendorAddress()->setCity('new city');
        $pendigUpdate->getVendorAddress()->setPostalCode('new code');
        $pendigUpdate->getVendorAddress()->setCountry($country);

        $this->manager->persist($pendigUpdate);
        $this->manager->flush();

        $this->sharedStorage->set('pendingUpdate', $pendigUpdate);
    }

    /**
     * @Then I should get validation error
     */
    public function iShouldGetValidationError()
    {
        $page = $this->getSession()->getPage();
        $label = $page->find('css', '.ui.red.pointing.label.sylius-validation-error');
    }

    /**
     * @Given vendor have logo attached to profile
     */
    public function vendorHaveLogoAttachedToProfile()
    {
        /** @var VendorInterface $vendor */
        $vendor = $this->sharedStorage->get('vendor');
        $path = 'path/to/file.png';
        $image = $this->vendorImageFactory->create($path, $vendor);
        $vendor->setImage($image);
        $this->sharedStorage->set('path', $path);
    }

    /**
     * @When I visit confirmation page
     */
    public function iVisitConfirmationPage()
    {
        $repository = $this->manager->getRepository(ProfileUpdate::class);
        $updateData = $repository->findAll();
        $token = $updateData[0]->getToken();
        $session = $this->getSession();
        $session->visit('/en_US/account/vendor/profile-update/' . $token);
    }

    /**
     * @Then Logo should be updated
     */
    public function imageShouldBeUpdated()
    {
        $oldImagePath = $this->sharedStorage->get('path');
        $session = $this->getSession();
        $session->visit('/en_US/vendors/vendor-slug');

        $page = $session->getPage();
        $logo = $page->find('css', '#vendor_logo');
        $newPath = $logo->getAttribute('src');
        Assert::notEq($oldImagePath, $newPath);
    }

    /**
     * @Given Vendor company name is :companyName tax ID is :taxId phone number is :phoneNumber
     */
    public function vendorCompanyNameIsTaxIdIsPhoneNumberIs(
        $companyName,
        $taxId,
        $phoneNumber
    ) {
        /** @var VendorInterface $vendor */
        $vendor = $this->sharedStorage->get('vendor');
        $vendor->setCompanyName($companyName);
        $vendor->setTaxIdentifier($taxId);
        $vendor->setPhoneNumber($phoneNumber);

        $this->manager->persist($vendor);
        $this->manager->flush();
        $this->sharedStorage->set('vendor', $vendor);
    }

    /**
     * @Then I should see form initialized with :companyName :taxId :phoneNumber
     */
    public function iShouldSeeAsDefaultFormValues(
        $companyName,
        $taxId,
        $phoneNumber
    ) {
        $page = $this->getSession()->getPage();
        $companyNameInput = $page->find('css', '#profile_companyName');
        $taxIdInput = $page->find('css', '#profile_taxIdentifier');
        $phoneNumberInput = $page->find('css', '#profile_phoneNumber');

        Assert::eq($companyName, $companyNameInput->getAttribute('value'));
        Assert::eq($taxId, $taxIdInput->getAttribute('value'));
        Assert::eq($phoneNumber, $phoneNumberInput->getAttribute('value'));
    }

    /**
     * @Given the channel has a menu taxon
     */
    public function theChannelHasAsAMenuTaxon()
    {
        /** @var ChannelInterface $channel */
        $channel = $this->sharedStorage->get('channel');
        $taxon = $this->taxonFactory->createNew();
        $taxon->setCode('menu_category');
        $taxon->setName('main');
        $taxon->setSlug('main');
        $taxon->enable();
        $channel->setMenuTaxon($taxon);

        $this->manager->persist($taxon);
        $this->manager->flush();
    }
}
