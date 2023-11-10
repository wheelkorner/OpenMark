<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace spec\BitBag\OpenMarketplace\Component\ProductListing\Validator;

use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\ListingPriceInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Validator\Constraint\ProductListingPriceConstraint;
use BitBag\OpenMarketplace\Component\ProductListing\Validator\ProductListingPriceValidator;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ProductListingPriceValidatorSpec extends ObjectBehavior
{
    public function let(ChannelRepositoryInterface $channelRepository): void
    {
        $this->beConstructedWith($channelRepository);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ProductListingPriceValidator::class);
        $this->shouldImplement(ConstraintValidatorInterface::class);
    }

    public function it_throws_an_exception_on_wrong_constraint(
        Constraint $constraint,
        DraftInterface $productDraft
    ): void {
        $this
            ->shouldThrow(UnexpectedTypeException::class)
            ->during('validate', [$productDraft, $constraint]);
    }

    public function it_adds_violation_if_no_product_listing_price_provided(
        ExecutionContextInterface $executionContext,
        ChannelRepositoryInterface $channelRepository,
        ChannelInterface $channel,
        DraftInterface $productDraft,
        ListingPriceInterface $productListingPrice
    ): void {
        $constraint = new ProductListingPriceConstraint();

        $this->initialize($executionContext);

        $channelRepository->findAll()->willReturn(new ArrayCollection([$channel->getWrappedObject()]));
        $productDraft->getProductListingPriceForChannel($channel)->willReturn($productListingPrice);
        $productListingPrice->getPrice()->willReturn(null);

        $this->validate($productDraft, $constraint);

        $executionContext->addViolation($constraint->message)
            ->shouldBeCalled();
    }

    public function it_does_not_add_violation_if_product_listing_price_provided(
        ExecutionContextInterface $executionContext,
        ChannelRepositoryInterface $channelRepository,
        ChannelInterface $channel,
        DraftInterface $productDraft,
        ListingPriceInterface $productListingPrice
    ): void {
        $constraint = new ProductListingPriceConstraint();

        $this->initialize($executionContext);

        $channelRepository->findAll()->willReturn(new ArrayCollection([$channel->getWrappedObject()]));
        $productDraft->getProductListingPriceForChannel($channel)->willReturn($productListingPrice);
        $productListingPrice->getPrice()->willReturn(123);

        $this->validate($productDraft, $constraint);

        $executionContext->addViolation($constraint->message)
            ->shouldNotBeCalled();
    }
}
