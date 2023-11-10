<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Api\Doctrine\QueryExtension\Vendor\VendorContextStrategy;

use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Core\Model\CustomerInterface;

final class CustomerFilterStrategy extends AbstractFilterStrategy implements FilterVendorStrategy
{
    protected function getSupportedClasses(): array
    {
        return [
            CustomerInterface::class,
        ];
    }

    public function filterByVendor(QueryBuilder $queryBuilder, VendorInterface $vendor): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->innerJoin(sprintf('%s.orders', $rootAlias), 'orders');
        $queryBuilder->andWhere('orders.vendor = :currentVendor');
        $queryBuilder->setParameter('currentVendor', $vendor->getId());
    }
}
