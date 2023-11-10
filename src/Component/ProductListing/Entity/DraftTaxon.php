<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\ProductListing\Entity;

use Ramsey\Uuid\UuidInterface;
use Sylius\Component\Core\Model\TaxonInterface;

class DraftTaxon implements DraftTaxonInterface
{
    protected mixed $id;

    protected ?UuidInterface $uuid = null;

    protected DraftInterface|null $productDraft;

    protected TaxonInterface|null $taxon;

    protected int|null $position;

    public function getId(): mixed
    {
        return $this->id;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(?UuidInterface $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getProductDraft(): ?DraftInterface
    {
        return $this->productDraft;
    }

    public function setProductDraft(?DraftInterface $productDraft): void
    {
        $this->productDraft = $productDraft;
    }

    public function getTaxon(): ?TaxonInterface
    {
        return $this->taxon;
    }

    public function setTaxon(?TaxonInterface $taxon): void
    {
        $this->taxon = $taxon;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }
}
