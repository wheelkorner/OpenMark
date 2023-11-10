<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Order\Factory;

use BitBag\OpenMarketplace\Component\Order\Entity\OrderInterface;
use BitBag\OpenMarketplace\Component\Order\Entity\ShipmentInterface;
use BitBag\OpenMarketplace\Component\Order\Resolver\VendorShippingMethodsResolverInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use Sylius\Component\Shipping\Exception\UnresolvedDefaultShippingMethodException;
use Sylius\Component\Shipping\Resolver\DefaultShippingMethodResolverInterface;

final class ShipmentFactory implements ShipmentFactoryInterface
{
    public function __construct(
        private string $shipmentFQN,
        private VendorShippingMethodsResolverInterface $defaultVendorShippingMethodResolver,
        private DefaultShippingMethodResolverInterface $defaultShippingMethodResolver
    ) {
    }

    public function createNew(): ShipmentInterface
    {
        /** @phpstan-ignore-next-line  */
        return new $this->shipmentFQN();
    }

    public function createNewWithOrder(OrderInterface $order): ShipmentInterface
    {
        $shipment = $this->createNew();
        $shipment->setOrder($order);

        return $shipment;
    }

    public function tryCreateNewWithOrderVendorAndDefaultShipment(
        OrderInterface $order,
        ?VendorInterface $vendor,
    ): ?ShipmentInterface {
        $shipment = $this->createNewWithOrder($order);

        try {
            if (null !== $vendor) {
                $shipment->setVendor($vendor);

                $defaultVendorShippingMethod = $this
                    ->defaultVendorShippingMethodResolver
                    ->getDefaultShippingMethod($vendor, $order->getChannel());
                $defaultShippingMethod = $defaultVendorShippingMethod->getShippingMethod();
            } else {
                $defaultShippingMethod = $this->defaultShippingMethodResolver->getDefaultShippingMethod($shipment);
            }

            $shipment->setMethod($defaultShippingMethod);

            return $shipment;
        } catch (UnresolvedDefaultShippingMethodException) {
            return null;
        }
    }
}
