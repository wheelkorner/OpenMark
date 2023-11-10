<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Common\Controller\Messaging;

use BitBag\OpenMarketplace\Component\Core\Common\Form\Type\Messaging\ConversationType;
use BitBag\OpenMarketplace\Component\Messaging\Entity\ConversationInterface;
use BitBag\OpenMarketplace\Component\Messaging\Entity\MessageInterface;
use BitBag\OpenMarketplace\Component\Messaging\MessagePersisterInterface;
use BitBag\OpenMarketplace\Component\Messaging\Repository\ConversationRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class CreateThreadAction
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private Environment $templatingEngine,
        private UrlGeneratorInterface $urlGenerator,
        private MessagePersisterInterface $messagePersister,
        private ConversationRepositoryInterface $conversationRepository
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $template = $request->attributes->get('_sylius')['template'];

        $form = $this->formFactory->create(ConversationType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $redirect = $request->attributes->get('_sylius')['redirect'];

            /** @var ConversationInterface $conversation */
            $conversation = $form->getData();

            $this->conversationRepository->add($conversation);

            $this->addConversationWithMessages($conversation);

            return new RedirectResponse($this->urlGenerator->generate($redirect, [
                'id' => $conversation->getId(),
            ]));
        }

        return new Response(
            $this->templatingEngine->render(
                $template,
                [
                    'form' => $form->createView(),
                ]
            )
        );
    }

    private function addConversationWithMessages(ConversationInterface $conversation): void
    {
        if (null === $conversation->getMessages()) {
            return;
        }

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
