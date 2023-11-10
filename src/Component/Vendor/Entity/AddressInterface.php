<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Vendor\Entity;

use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface AddressInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getCountry(): ?CountryInterface;

    public function setCountry(?CountryInterface $country): void;

    public function getCity(): ?string;

    public function setCity(?string $city): void;

    public function getStreet(): ?string;

    public function setStreet(?string $street): void;

    public function getPostalCode(): ?string;

    public function setPostalCode(?string $postalCode): void;
}
