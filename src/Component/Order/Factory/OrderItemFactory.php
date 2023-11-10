<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Order\Factory;

use Sylius\Component\Core\Model\OrderItemInterface;

final class OrderItemFactory implements OrderItemFactoryInterface
{
    public function __construct(
        private string $orderItemFQN
    ) {
    }

    public function createNew(): OrderItemInterface
    {
        /** @phpstan-ignore-next-line */
        return new $this->orderItemFQN();
    }
}
