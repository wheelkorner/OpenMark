<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\ProductListing\Validator;

use BitBag\OpenMarketplace\Component\ProductListing\Entity\ListingPriceInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Validator\Constraint\ProductListingPriceConstraint;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ProductListingPriceValidator extends ConstraintValidator
{
    public function __construct(
        private ChannelRepositoryInterface $channelRepository
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductListingPriceConstraint) {
            throw new UnexpectedTypeException($constraint, ProductListingPriceConstraint::class);
        }

        $channels = $this->channelRepository->findAll();

        foreach ($channels as $channel) {
            /** @var ListingPriceInterface|null $productListingPrice */
            $productListingPrice = $value->getProductListingPriceForChannel($channel);
            if (null === $productListingPrice || null === $productListingPrice->getPrice()) {
                $this->context->addViolation($constraint->message);

                return;
            }
        }
    }
}
