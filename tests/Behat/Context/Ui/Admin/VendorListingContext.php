<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace Tests\BitBag\OpenMarketplace\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\DocumentElement;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AdminUserExampleFactory;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\Customer;
use Webmozart\Assert\Assert;

final class VendorListingContext extends RawMinkContext implements Context
{
    private EntityManagerInterface $entityManager;

    private AdminUserExampleFactory $adminUserExample;

    private ExampleFactoryInterface $vendorExampleFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        AdminUserExampleFactory $adminUserExample,
        ExampleFactoryInterface $vendorExampleFactory,
        ) {
        $this->entityManager = $entityManager;
        $this->adminUserExample = $adminUserExample;
        $this->vendorExampleFactory = $vendorExampleFactory;
    }

    /**
     * @Given There is an admin user :username with password :password
     */
    public function thereIsAnAdminUserWithPassword($username, $password)
    {
        $admin = $this->adminUserExample->create();
        $admin->setUsername($username);
        $admin->setPlainPassword($password);
        $this->entityManager->persist($admin);
        $this->entityManager->flush();
    }

    /**
     * @Given I am logged in as an admin
     */
    public function iAmLoggedInAsAnAdmin()
    {
        $this->visitPath('/admin/login');
        $this->getPage()->fillField('Username', 'admin');
        $this->getPage()->fillField('Password', 'admin');
        $this->getPage()->pressButton('Login');
    }

    /**
     * @Given There are :count vendors listed
     */
    public function thereAreVendors($count)
    {
        for ($i = 0; $i < $count; ++$i) {
            $options = [
                'company_name' => 'vendor ' . $i,
                'phone_number' => 'vendorPhone' . $i,
                'tax_identifier' => 'vendorTax' . $i,
                'slug' => 'vendor-' . $i,
                'description' => 'description',
            ];

            $vendor = $this->vendorExampleFactory->create($options);

            $this->entityManager->persist($vendor);
        }
        $this->entityManager->flush();
    }

    /**
     * @Then I should see :count vendor rows
     */
    public function iShouldSeeVendorRows($count)
    {
        $rows = $this->getPage()->findAll('css', 'table > tbody > tr');
        Assert::notEmpty($rows, 'Could not find any rows');
        Assert::eq($count, count($rows), 'Rows numbers are not equal');
    }

    /**
     * @Then page should contain valid customer :email link
     */
    public function iShouldSeeValidCustomerLink(string $email)
    {
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['email' => $email]);
        $link = sprintf('<a href="/admin/customers/%d">%s</a>', $customer->getId(), $email);
        Assert::contains($this->getPage()->getHtml(), $link);
    }

    /**
     * @Given /^I should see vendors commission data$/
     */
    public function iShouldSeeVendorsCommissionData()
    {
        $content = $this->getPage()->getText();
        Assert::contains($content, 'Commission (%)');
        Assert::contains($content, 'Commission Type');
    }

    /**
     * @return DocumentElement
     */
    private function getPage()
    {
        return $this->getSession()->getPage();
    }
}
