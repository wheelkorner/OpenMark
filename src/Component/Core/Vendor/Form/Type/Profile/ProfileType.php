<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Vendor\Form\Type\Profile;

use BitBag\OpenMarketplace\Component\Core\Common\Form\Type\VendorAddressType;
use BitBag\OpenMarketplace\Component\Vendor\Entity\Vendor;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

final class ProfileType extends AbstractResourceType
{
    public function __construct(
        string $dataClass,
        array $validationGroups,
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'open_marketplace.ui.company_name',
            ])
            ->add('taxIdentifier', TextType::class, [
                'label' => 'open_marketplace.ui.tax_identifier',
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'open_marketplace.ui.phone_number',
            ])
            ->add('image', LogoImageType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [new Valid(['groups' => 'VendorLogo'])],
            ])
            ->add('backgroundImage', BackgroundImageType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [new Valid(['groups' => 'VendorBackground'])],
            ])
            ->add('vendorAddress', VendorAddressType::class, [
                'label' => 'open_marketplace.ui.company_address',
                'constraints' => [new Valid()],
            ])
            ->add('description', TextType::class, [
                'label' => 'open_marketplace.ui.description',
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vendor::class,
            'validation_groups' => $this->validationGroups,
        ]);
    }
}
