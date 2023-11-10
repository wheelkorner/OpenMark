<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Api\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use BitBag\OpenMarketplace\Component\Core\Api\Messenger\Command\VendorSlugAwareInterface;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use BitBag\OpenMarketplace\Component\Vendor\Generator\SlugGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class VendorSlugEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SlugGeneratorInterface $vendorSlugGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['generateSlug', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function generateSlug(ViewEvent $event): void
    {
        $vendorSlugAware = $event->getControllerResult();
        if (!$vendorSlugAware instanceof VendorInterface &&
            !$vendorSlugAware instanceof VendorSlugAwareInterface
        ) {
            return;
        }

        if (empty($vendorSlugAware->getCompanyName())) {
            return;
        }

        $method = $event->getRequest()->getMethod();
        if (!in_array($method, [Request::METHOD_POST, Request::METHOD_PUT], true)) {
            return;
        }

        $slug = $this->vendorSlugGenerator->generateSlug($vendorSlugAware->getCompanyName());
        $vendorSlugAware->setSlug($slug);
    }
}
