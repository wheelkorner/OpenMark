<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\ProductListing\Entity;

use BitBag\OpenMarketplace\Component\Product\Entity\ProductInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

class Listing implements ListingInterface
{
    protected int $id;

    protected ?UuidInterface $uuid = null;

    protected ?string $code;

    protected bool $enabled = true;

    protected bool $removed = false;

    protected string $verificationStatus = DraftInterface::STATUS_CREATED;

    protected VendorInterface $vendor;

    protected ?DraftInterface $latestDraft = null;

    /** @var Collection<int, DraftInterface> */
    protected Collection $productDrafts;

    protected ?ProductInterface $product = null;

    protected ?DateTimeInterface $publishedAt = null;

    protected ?DateTimeInterface $lastVerifiedAt = null;

    protected DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->productDrafts = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): int
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function remove(): void
    {
        $this->removed = true;
    }

    public function restore(): void
    {
        $this->removed = false;
    }

    public function getVerificationStatus(): string
    {
        return $this->verificationStatus;
    }

    public function setVerificationStatus(string $verificationStatus): void
    {
        $this->verificationStatus = $verificationStatus;
    }

    public function getVendor(): VendorInterface
    {
        return $this->vendor;
    }

    public function setVendor(VendorInterface $vendor): void
    {
        $this->vendor = $vendor;
    }

    public function getLatestDraft(): ?DraftInterface
    {
        if (null === $this->latestDraft) {
            $lastDraft = $this->getProductDrafts()->last();

            return $lastDraft ?: null;
        }

        return $this->latestDraft;
    }

    public function getProductDrafts(): Collection
    {
        return $this->productDrafts;
    }

    public function insertDraft(DraftInterface $newDraft): void
    {
        if (null !== $this->getLatestDraft()) {
            $newDraft->setVersionNumber($this->getLatestDraft()->getVersionNumber());
            $newDraft->incrementVersion();
        }

        $newDraft->setProductListing($this);

        $this->productDrafts->add($newDraft);

        $this->latestDraft = $newDraft;
        $this->verificationStatus = DraftInterface::STATUS_CREATED;
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }

    public function setProduct(?ProductInterface $product): void
    {
        $this->product = $product;
    }

    public function getPublishedAt(): ?DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(DateTimeInterface $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getLastVerifiedAt(): ?DateTimeInterface
    {
        return $this->lastVerifiedAt;
    }

    public function setLastVerifiedAt(DateTimeInterface $lastVerifiedAt): void
    {
        $this->lastVerifiedAt = $lastVerifiedAt;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getAnyTranslationName(): ?string
    {
        $latestDraft = $this->getLatestDraft();

        return $latestDraft?->getAnyTranslationName();
    }

    public function needsNewDraft(): bool
    {
        return null !== $this->getLatestDraft() &&
            false === $this->getLatestDraft()->isCreated();
    }

    public function canBeVerified(): bool
    {
        return null !== $this->getLatestDraft() &&
            DraftInterface::STATUS_CREATED === $this->getLatestDraft()->getStatus();
    }

    public function sendToVerification(DraftInterface $productDraft): void
    {
        $productDraft->sendToVerification();

        $this->verificationStatus = $productDraft->getStatus();
        $this->publishedAt = $productDraft->getPublishedAt();
    }

    public function accept(): void
    {
        /** @var DraftInterface $latestDraft */
        $latestDraft = $this->getLatestDraft();
        Assert::isInstanceOf($latestDraft, DraftInterface::class);

        $latestDraft->accept();

        $this->verificationStatus = $latestDraft->getStatus();
        $this->lastVerifiedAt = $latestDraft->getVerifiedAt();
    }

    public function reject(): void
    {
        /** @var DraftInterface $latestDraft */
        $latestDraft = $this->getLatestDraft();
        Assert::isInstanceOf($latestDraft, DraftInterface::class);

        $latestDraft->reject();

        $this->verificationStatus = $latestDraft->getStatus();
        $this->lastVerifiedAt = $latestDraft->getVerifiedAt();
    }
}
