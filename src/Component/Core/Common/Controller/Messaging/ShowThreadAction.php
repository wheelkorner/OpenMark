<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Common\Controller\Messaging;

use BitBag\OpenMarketplace\Component\Core\Common\Form\Type\Messaging\MessageType;
use BitBag\OpenMarketplace\Component\Core\Common\Security\Voter\ConversationOwningVoter;
use BitBag\OpenMarketplace\Component\Messaging\Entity\Conversation;
use BitBag\OpenMarketplace\Component\Messaging\Repository\ConversationRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

final class ShowThreadAction
{
    public function __construct(
        private Environment $templatingEngine,
        private FormFactoryInterface $formFactory,
        private ConversationRepositoryInterface $conversationRepository,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function __invoke(int $id, Request $request): Response
    {
        $template = $request->attributes->get('_sylius')['template'];

        $form = $this->formFactory->create(MessageType::class);

        /** @var Conversation $conversation */
        $conversation = $this->conversationRepository->find($id);

        if (!$this->authorizationChecker->isGranted(ConversationOwningVoter::UPDATE, $conversation)) {
            throw new AccessDeniedException();
        }

        return new Response(
            $this->templatingEngine->render(
                $template,
                [
                    'form' => $form->createView(),
                    'conversation' => $conversation,
                ]
            )
        );
    }
}
