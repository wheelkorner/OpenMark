<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Vendor\Form\Type\ProductListing;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\CoreBundle\Form\Type\ChannelCollectionType;
use Sylius\Bundle\ShippingBundle\Form\Type\ShippingCategoryChoiceType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

final class ListingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'sylius.ui.code',
                'disabled' => ($builder->getData()->getCode()),
            ])
            ->add('shippingRequired', CheckboxType::class, [
                'label' => 'sylius.form.variant.shipping_required',
                'required' => false,
            ])
            ->add('shippingCategory', ShippingCategoryChoiceType::class, [
                'required' => false,
                'placeholder' => 'sylius.ui.no_requirement',
                'label' => 'sylius.form.product_variant.shipping_category',
            ])
            ->add('translations', DraftTranslationsCollectionType::class, [
                'entry_type' => DraftTranslationType::class,
                'label' => 'sylius.form.product.translations',
                'attr' => [
                    'class' => 'ui styled fluid accordion',
                ],
                'constraints' => [new Valid(['groups' => 'sylius'])],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'open_marketplace.ui.save_draft',
                'attr' => [
                    'class' => 'ui primary big button',
                ],
            ])
            ->add('attributes', CollectionType::class, [
                'entry_type' => DraftAttributeValueType::class,
                'required' => false,
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'label' => false,
            ])
            ->add('channels', ChannelChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'label' => 'sylius.form.product.channels',
            ])
            ->add('mainTaxon', TaxonAutocompleteChoiceType::class, [
                'label' => 'sylius.form.product.main_taxon',
                'required' => false,
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
                $product = $event->getData();
                $form = $event->getForm();
            })
            ->add('images', CollectionType::class, [
                'entry_type' => DraftImageType::class,
                'entry_options' => ['product' => $options['data']],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'required' => false,
                'label' => 'sylius.form.product.images',
                'block_name' => 'entry',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $productDraft = $event->getData();

            $event->getForm()
                ->add('productListingPrices', ChannelCollectionType::class, [
                    'entry_type' => DraftPriceType::class,
                    'entry_options' => fn (ChannelInterface $channel) => [
                        'channel' => $channel,
                        'product_draft' => $productDraft,
                        'required' => false,
                    ],
                    'label' => 'sylius.form.variant.price',
                ])
                ->add('productDraftTaxons', DraftTaxonAutocompleteChoiceType::class, [
                    'label' => 'sylius.form.product.taxons',
                    'productDraft' => $productDraft,
                    'multiple' => true,
                    'required' => false,
                ]);
        });
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_product';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => true,
            'validation_groups' => 'sylius',
        ]);
    }
}
