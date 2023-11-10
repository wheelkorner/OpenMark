<?php

declare(strict_types=1);

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

namespace BitBag\OpenMarketplace\Component\Core\Api\Controller\Vendor;

use BitBag\OpenMarketplace\Component\Messaging\Entity\Conversation;
use BitBag\OpenMarketplace\Component\Messaging\Entity\ConversationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class AcceptArchiveConversationAction
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(ConversationInterface $data): ConversationInterface|Response
    {
        if ($data->isConversationReportedToArchive()) {
            $data->setStatus(Conversation::STATUS_CLOSED);

            $this->entityManager->persist($data);
            $this->entityManager->flush();

            return $data;
        }

        return new Response('', Response::HTTP_BAD_REQUEST);
    }
}
