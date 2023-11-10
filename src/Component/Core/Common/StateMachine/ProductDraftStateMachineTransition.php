<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Common\StateMachine;

use BitBag\OpenMarketplace\Component\ProductListing\DraftTransitions;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use SM\Factory\FactoryInterface;

final class ProductDraftStateMachineTransition implements ProductDraftStateMachineTransitionInterface
{
    public function __construct(
        private FactoryInterface $productDraftStateMachineFactory
    ) {
    }

    public function applyIfCan(
        DraftInterface $productDraft,
        string $transition
    ): void {
        $stateMachine = $this->productDraftStateMachineFactory->get(
            $productDraft,
            DraftTransitions::GRAPH
        );

        if (!$stateMachine->can($transition)) {
            return;
        }

        $stateMachine->apply($transition);
    }
}
