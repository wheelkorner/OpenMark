<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\ProductListing\Repository;

use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\ListingInterface;

interface DraftRepositoryInterface
{
    public function save(DraftInterface $productDraft): void;

    public function findLatestDraft(ListingInterface $productListing): ?DraftInterface;

    /**
     * @return mixed
     */
    public function find(
        int $id,
        ?int $lockMode = null,
        ?int $lockVersion = null
    );
}
