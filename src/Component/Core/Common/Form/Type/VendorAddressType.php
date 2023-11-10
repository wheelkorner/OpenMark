<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Common\Form\Type;

use BitBag\OpenMarketplace\Component\Vendor\Entity\Address;
use Sylius\Component\Addressing\Model\Country;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class VendorAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'label' => 'open_marketplace.ui.country',
            ])
            ->add('city', TextType::class, [
                'label' => 'open_marketplace.ui.city',
            ])
            ->add('street', TextType::class, [
                'label' => 'open_marketplace.ui.street',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'open_marketplace.ui.postal_code',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
