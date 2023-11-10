<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Admin\Controller\ProductListing;

use BitBag\OpenMarketplace\Component\Core\Common\Form\Type\Messaging\ConversationType;
use BitBag\OpenMarketplace\Component\Messaging\Entity\Conversation;
use BitBag\OpenMarketplace\Component\Messaging\Entity\ConversationInterface;
use BitBag\OpenMarketplace\Component\Messaging\Entity\MessageInterface;
use BitBag\OpenMarketplace\Component\Messaging\MessagePersisterInterface;
use BitBag\OpenMarketplace\Component\Messaging\Repository\ConversationRepositoryInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\DraftInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Entity\ListingInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Repository\DraftRepositoryInterface;
use BitBag\OpenMarketplace\Component\ProductListing\Repository\ListingRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class ShowAction
{
    public function __construct(
        private ListingRepositoryInterface $productListingRepository,
        private Environment $twig,
        private DraftRepositoryInterface $productDraftRepository,
        private ConversationRepositoryInterface $conversationRepository,
        private FormFactoryInterface $formFactory,
        private MessagePersisterInterface $messagePersister,
        private RouterInterface $router,
        ) {
    }

    public function __invoke(Request $request): Response
    {
        /** @var ListingInterface $productListing */
        $productListing = $this->productListingRepository->find($request->attributes->get('id'));

        /** @var DraftInterface $latestProductDraft */
        $latestProductDraft = $this->productDraftRepository->findLatestDraft($productListing);

        $conversation = new Conversation();

        $form = $this->formFactory->create(ConversationType::class, $conversation);

        $form->handleRequest($request);
        $draftViewURL = $this->router->generate(
            'open_marketplace_vendor_product_listings_show',
            ['id' => $latestProductDraft->getId()],
            UrlGenerator::ABSOLUTE_URL
        );

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ConversationInterface $conversation */
            $conversation = $form->getData();
            $conversation->setShopUser($productListing->getVendor()->getShopUser());
            $conversation->setRejectedListingURL($draftViewURL);
            $this->conversationRepository->add($conversation);

            $this->addConversationWithMessages($conversation);

            return new RedirectResponse($this->router->generate(
                'open_marketplace_admin_product_listing_reject',
                ['id' => $request->attributes->get('id')]
            ));
        }

        return new Response(
            $this->twig->render('Context/Admin/ProductListing/show.html.twig', [
                'productListing' => $productListing,
                'productDraft' => $latestProductDraft,
                'form' => $form->createView(),
            ])
        );
    }

    private function addConversationWithMessages(ConversationInterface $conversation): void
    {
        if (null !== $conversation->getMessages()) {
            /** @var MessageInterface $message */
            foreach ($conversation->getMessages()->toArray() as $message) {
                $this->messagePersister->createWithConversation(
                    $conversation->getId(),
                    $message,
                    $message->getFile(),
                );
            }
        }
    }
}
