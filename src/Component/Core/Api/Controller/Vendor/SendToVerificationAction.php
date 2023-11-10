<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Api\Controller\Vendor;

use BitBag\OpenMarketplace\Component\Core\Common\StateMachine\ProductDraftStateMachineTransitionInterface;
use BitBag\OpenMarketplace\Component\ProductListing\DraftTransitions;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\ListingInterface;
use Webmozart\Assert\Assert;

final class SendToVerificationAction
{
    public function __construct(
        private ProductDraftStateMachineTransitionInterface $productDraftStateMachineTransition
    ) {
    }

    public function __invoke(ListingInterface $data): ListingInterface
    {
        if ($data->canBeVerified()) {
            /** @var DraftInterface $latestDraft */
            $latestDraft = $data->getLatestDraft();
            Assert::notNull($latestDraft);

            $this->productDraftStateMachineTransition->applyIfCan($latestDraft, DraftTransitions::TRANSITION_SEND_TO_VERIFICATION);
        }

        return $data;
    }
}
