<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\ProductListing\Entity;

use BitBag\OpenMarketplace\Component\Core\Api\UuidAwareInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface DraftTaxonInterface extends ResourceInterface, UuidAwareInterface
{
    public function getProductDraft(): ?DraftInterface;

    public function setProductDraft(?DraftInterface $productDraft): void;

    public function getTaxon(): ?TaxonInterface;

    public function setTaxon(?TaxonInterface $taxon): void;

    public function getPosition(): ?int;

    public function setPosition(?int $position): void;
}
