<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Common\Controller\Resource;

use BitBag\OpenMarketplace\Component\Core\Vendor\Exception\ShopUserNotFoundException;
use BitBag\OpenMarketplace\Component\Vendor\Entity\ProfileUpdate\ProfileUpdate;
use BitBag\OpenMarketplace\Component\Vendor\Entity\Vendor;
use BitBag\OpenMarketplace\Component\Vendor\Entity\VendorInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Exception\UpdateHandlingException;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

final class VendorController extends ResourceController
{
    public function createAction(Request $request): Response
    {
        try {
            return parent::createAction($request);
        } catch (ShopUserNotFoundException $exception) {
            return $this->redirectToRoute('sylius_shop_login');
        } catch (TokenNotFoundException $exception) {
            return $this->redirectToRoute('sylius_shop_login');
        }
    }

    public function customUpdateAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);
        $this->isGrantedOr403($configuration, ResourceActions::UPDATE);

        $vendor = $this->container->get('bitbag.open_marketplace.component.vendor.context.vendor')->getVendor();
        $pendingUpdate = $this->manager->getRepository(ProfileUpdate::class)
            ->findOneBy(['vendor' => $vendor]);

        if (null !== $pendingUpdate) {
            $this->addFlash('error', 'sylius.user.verify_email_request');

            return $this->redirectToRoute('open_marketplace_vendor_profile_details');
        }

        $resource = $vendor;

        $form = $this->resourceFormFactory->create($configuration, $resource);

        $form->handleRequest($request);
        if (
            in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)
            && $form->isSubmitted()
            && $form->isValid()
        ) {
            $resource = $form->getData();

            try {
                $image = $resource->getImage();
                $backgroundImage = $resource->getBackgroundImage();
                $this->container->get('bitbag.open_marketplace.component.vendor.profile_updater')->createPendingVendorProfileUpdate(
                    $form->getData(),
                    $vendor,
                    $image,
                    $backgroundImage
                );
                if ($image) {
                    $this->manager->remove($image);
                }
                if ($backgroundImage) {
                    $this->manager->remove($backgroundImage);
                }

                $vendor->setEditedAt(new \DateTime());
                $this->manager->flush();
            } catch (UpdateHandlingException $exception) {
                if (!$configuration->isHtmlRequest()) {
                    return $this->createRestView($configuration, $form, $exception->getApiResponseCode());
                }

                $this->flashHelper->addErrorFlash($configuration, $exception->getFlash());

                return $this->redirectHandler->redirectToReferer($configuration);
            }

            if ($configuration->isHtmlRequest()) {
                $this->flashHelper->addSuccessFlash($configuration, ResourceActions::UPDATE, $resource);
            }

            if (!$configuration->isHtmlRequest()) {
                if ($configuration->getParameters()->get('return_content', false)) {
                    return $this->createRestView($configuration, $resource, Response::HTTP_OK);
                }

                return $this->createRestView($configuration, null, Response::HTTP_NO_CONTENT);
            }

            return $this->redirectHandler->redirectToResource($configuration, $resource);
        }

        if (!$configuration->isHtmlRequest()) {
            return $this->createRestView($configuration, $form, Response::HTTP_BAD_REQUEST);
        }

        return $this->render($configuration->getTemplate(ResourceActions::UPDATE . '.html'), [
            'configuration' => $configuration,
            'metadata' => $this->metadata,
            'resource' => $resource,
            $this->metadata->getName() => $resource,
            'form' => $form->createView(),
        ]);
    }

    public function showVendorProfileAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);

        /** @var ResourceInterface $resource */
        $resource = $this->container->get('bitbag.open_marketplace.component.vendor.context.vendor')->getVendor();
        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $resource);

        if ($configuration->isHtmlRequest()) {
            return $this->render($configuration->getTemplate(ResourceActions::SHOW . '.html'), [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resource' => $resource,
                $this->metadata->getName() => $resource,
            ]);
        }

        return $this->createRestView($configuration, $resource);
    }

    public function verifyVendorAction(Request $request): Response
    {
        $vendorId = $request->attributes->get('id', 0);
        $vendorRepository = $this->manager->getRepository(Vendor::class);

        $currentVendor = $vendorRepository->findOneBy(['id' => $vendorId]);

        if (null === $currentVendor) {
            throw new NotFoundHttpException(sprintf('Vendor with id %d has not been found', $vendorId));
        }

        $currentVendor->setStatus(VendorInterface::STATUS_VERIFIED);

        $this->manager->flush();

        $this->addFlash('success', 'open_marketplace.ui.vendor_verified');

        return $this->redirectToRoute('open_marketplace_admin_vendor_index');
    }

    public function enablingVendorAction(Request $request): Response
    {
        $vendorId = $request->attributes->get('id', 0);
        $vendorRepository = $this->manager->getRepository(Vendor::class);
        $currentVendor = $vendorRepository->findOneBy(['id' => $vendorId]);
        if ($currentVendor) {
            $currentVendor->setEnabled(!$currentVendor->isEnabled());
            $messageSuffix = $currentVendor->isEnabled() ? 'enabled' : 'disabled';

            $this->manager->flush();
            $this->addFlash('success', 'open_marketplace.ui.vendor_' . $messageSuffix);
        }

        return $this->redirectToRoute('open_marketplace_admin_vendor_index');
    }
}
