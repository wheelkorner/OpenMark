<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\ProductListing\Repository;

use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

final class DraftAttributeRepository extends EntityRepository implements DraftAttributeRepositoryInterface
{
    public function findVendorDraftAttributes(VendorInterface $vendor): array
    {
        $queryBuilder = $this->findVendorDraftAttributesQuery($vendor);

        return $queryBuilder
            ->getQuery()
            ->getResult()
            ;
    }

    public function findVendorDraftAttributesQuery(VendorInterface $vendor): QueryBuilder
    {
        $vendorId = $vendor->getId();

        return $this->createQueryBuilder('o')
            ->andWhere('o.vendor = :vendor')
            ->setParameter('vendor', $vendorId)
            ;
    }
}
