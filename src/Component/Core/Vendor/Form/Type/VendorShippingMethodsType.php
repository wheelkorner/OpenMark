<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Vendor\Form\Type;

use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use BitBag\OpenMarketplace\Component\Vendor\Factory\VendorShippingMethodFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\CoreBundle\Form\Type\ChannelCollectionType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class VendorShippingMethodsType extends AbstractResourceType
{
    public function __construct(
        private ShippingMethodRepositoryInterface $shippingMethodRepository,
        private VendorShippingMethodFactoryInterface $vendorShippingMethodFactory,
        private EntityManagerInterface $entityManager,
        string $dataClass,
        array $validationGroups = []
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channels', ChannelCollectionType::class, [
                'entry_type' => VendorShippingMethodChoiceType::class,
                'entry_options' => fn (ChannelInterface $channel) => [
                    'required' => true,
                    'multiple' => true,
                    'label' => $channel->getName(),
                    'expanded' => true,
                    'channel' => $channel,
                    'vendor' => $options['data'],
                ],
                'mapped' => false,
                'label' => 'open_marketplace.ui.shipping_methods',
            ])->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
                /** @var VendorInterface $vendor */
                $vendor = $options['data'];

                $vendor->getShippingMethods()->clear();
                $this->entityManager->flush();

                if (isset($event->getData()['channels'])) {
                    $channels = $event->getData()['channels'];
                    foreach ($channels as $key => $shippingMethods) {
                        foreach ($shippingMethods as $code) {
                            /** @var ShippingMethodInterface $shippingMethod */
                            $shippingMethod = $this->shippingMethodRepository->findOneBy(['code' => $code]);

                            $vendorShippingMethod = $this
                                ->vendorShippingMethodFactory
                                ->createNewWithChannelCodeShippingAndVendor(
                                    $key,
                                    $shippingMethod,
                                    $vendor
                                );
                            $vendor->addShippingMethod($vendorShippingMethod);
                        }
                    }
                }
            });
    }
}
