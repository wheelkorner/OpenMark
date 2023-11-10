<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Api\Validator;

use BitBag\OpenMarketplace\Component\Vendor\Entity\ShopUserInterface;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class UniqueShopUserVendorValidator extends ConstraintValidator
{
    public function __construct(
        private UserContextInterface $userContext
    ) {
    }

    /** @param UniqueShopUserVendor $constraint */
    public function validate($value, Constraint $constraint): void
    {
        Assert::isInstanceOf($constraint, UniqueShopUserVendor::class);

        /** @var ShopUserInterface $user */
        $user = $this->userContext->getUser();
        Assert::isInstanceOf($user, ShopUserInterface::class);

        if (null !== $user->getVendor()) {
            $this->context->addViolation(
                $constraint->message
            );
        }
    }
}
