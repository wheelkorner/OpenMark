<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Api\Controller\Vendor;

use BitBag\OpenMarketplace\Component\ProductListing\Entity\ListingInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DeleteProductListingAction
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(ListingInterface $data): Response
    {
        $data->remove();

        if (null !== $product = $data->getProduct()) {
            $product->setEnabled(false);
            $this->entityManager->persist($product);
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
