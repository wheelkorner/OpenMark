<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Component\Core\Common\Controller\Resource;

use BitBag\OpenMarketplace\Component\Order\Entity\OrderInterface;
use Sylius\Bundle\CoreBundle\Controller\OrderController as BaseOrderController;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Bundle\ShippingBundle\Form\Type\ShipmentShipType;
use Sylius\Component\Resource\Exception\UpdateHandlingException;
use Sylius\Component\Resource\ResourceActions;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Webmozart\Assert\Assert;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

use Symfony\Component\Dotenv\Dotenv;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class OrderController extends BaseOrderController
{

    public function indexAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::INDEX);
        $resources = $this->resourcesCollectionProvider->get($configuration, $this->repository);

        $this->eventDispatcher->dispatchMultiple(ResourceActions::INDEX, $configuration, $resources);

        if ($configuration->isHtmlRequest()) {
            return $this->render($configuration->getTemplate(ResourceActions::INDEX . '.html'), [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resources' => $resources,
                $this->metadata->getPluralName() => $resources,
            ]);
        }

        return $this->createRestView($configuration, $resources);
    }

    public function showAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);

        /** @var OrderInterface $resource */
        $resource = $this->findOr404($configuration);

        if (null === $resource->getPrimaryOrder()) {
            return $this->redirectToRoute('open_marketplace_vendor_orders_listing');
        }

        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $resource);

        if ($configuration->isHtmlRequest()) {
            return $this->render($configuration->getTemplate(ResourceActions::SHOW . '.html'), [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resource' => $resource,
                'form' => $this->createForm(ShipmentShipType::class)->createView(),
                $this->metadata->getName() => $resource,
            ]);
        }

        return $this->createRestView($configuration, $resource);
    }

    public function updateAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::UPDATE);
        $resource = $this->findOr404($configuration);

        $form = $this->resourceFormFactory->create($configuration, $resource);
        $form->handleRequest($request);

        if (
            in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)
            && $form->isSubmitted()
            && $form->isValid()
        ) {
            $resource = $form->getData();

            /** @var ResourceControllerEvent $event */
            $event = $this->eventDispatcher->dispatchPreEvent(ResourceActions::UPDATE, $configuration, $resource);

            if ($event->isStopped() && !$configuration->isHtmlRequest()) {
                throw new HttpException($event->getErrorCode(), $event->getMessage());
            }
            if ($event->isStopped()) {
                $this->flashHelper->addFlashFromEvent($configuration, $event);

                $eventResponse = $event->getResponse();
                if (null !== $eventResponse) {
                    return $eventResponse;
                }

                return $this->redirectHandler->redirectToResource($configuration, $resource);
            }

            try {
                $splitOrderByVendorProcessor = $this->container->get('bitbag.open_marketplace.component.order.processor.split_order_by_vendor');
                $orders = $splitOrderByVendorProcessor->process($resource);

                foreach ($orders as $order) {
                    $this->resourceUpdateHandler->handle($order, $configuration, $this->manager);
                }
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

            $postEvent = $this->eventDispatcher->dispatchPostEvent(ResourceActions::UPDATE, $configuration, $resource);

            if (!$configuration->isHtmlRequest()) {
                if ($configuration->getParameters()->get('return_content', false)) {
                    return $this->createRestView($configuration, $resource, Response::HTTP_OK);
                }

                return $this->createRestView($configuration, null, Response::HTTP_NO_CONTENT);
            }

            $postEventResponse = $postEvent->getResponse();
            if (null !== $postEventResponse) {
                return $postEventResponse;
            }

            return $this->redirectHandler->redirectToResource($configuration, $resource);
        }

        if (!$configuration->isHtmlRequest()) {
            return $this->createRestView($configuration, $form, Response::HTTP_BAD_REQUEST);
        }

        $initializeEvent = $this->eventDispatcher->dispatchInitializeEvent(ResourceActions::UPDATE, $configuration, $resource);
        $initializeEventResponse = $initializeEvent->getResponse();
        if (null !== $initializeEventResponse) {
            return $initializeEventResponse;
        }

        return $this->render($configuration->getTemplate(ResourceActions::UPDATE . '.html'), [
            'configuration' => $configuration,
            'metadata' => $this->metadata,
            'resource' => $resource,
            $this->metadata->getName() => $resource,
            'form' => $form->createView(),
        ]);
    }

    public function thankYouAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $orderId = $request->getSession()->get('sylius_order_id', null);

        if (null === $orderId) {
            $options = $configuration->getParameters()->get('after_failure');

            return $this->redirectHandler->redirectToRoute(
                $configuration,
                $options['route'] ?? 'sylius_shop_homepage',
                $options['parameters'] ?? []
            );
        }

        $request->getSession()->remove('sylius_order_id');
        $order = $this->repository->find($orderId);
        Assert::notNull($order);

        return $this->render(
            $configuration->getParameters()->get('template'),
            [
                'order' => $order,
            ]
        );
    }

    public function getKanguSimular(): Response
    {
        $productVariantRepository = $this->getDoctrine()->getRepository(ProductVariantInterface::class);
        $productVariant = $productVariantRepository->find(1);

        $costumer = $this->container->get('bitbag.open_marketplace.component.vendor.repository.vendor')->find(3);
        $cepDestino= $costumer->getVendorAddress()->getPostalCode();

        $vendor = $this->container->get('bitbag.open_marketplace.component.vendor.context.vendor')->getVendor();
        $vendorAddress = $vendor->getVendorAddress();

        if (!$vendorAddress->__isInitialized()) {
            $vendorAddress->__load();
        }

        $cepOrigem = $vendorAddress->getPostalCode();
        // $dotenv = new Dotenv();
        // $dotenv->load('/srv/open_marketplace/.env');
        
        // $url = $_ENV['KANGU_API_URL'].'simular';
        // $token = $_ENV['KANGU_API_KEY'];

        $url = "https://portal.kangu.com.br/tms/transporte/simular";
        $token = "c3c2971bd4a106ba7b9e30c3e428814d88d2c335d80d225e5337df81419089cf";
        
        $requestData = [
            "cepOrigem" => $cepOrigem, // Obrigatorio
            "cepDestino" => $cepDestino, // Obrigatorio

            "vlrMerc" => 0.01, // Não aceita zero - Nem valores acima de 10.000,00
            "pesoMerc" => 0,// sem validacao
            "volumes" => [
                [
                    "peso" => 0,// $productVariant->getWeight(), // sem validacao
                    "altura" => 0,// $productVariant->getHeight(), // sem validacao
                    "largura" => 0, //$productVariant->getWidth(), // sem validacao
                    "comprimento" => 0, //$productVariant->getDepth(), // sem validacao
                    "tipo" => "string",
                    "valor" => 1,
                    "quantidade" => 0// sem validacao
                ]
            ],
            "produtos" => [
                [
                    "peso" => $productVariant->getWeight(),  // máx 30 - aceita zero
                    "altura" => $productVariant->getHeight(), // acima de 70 taxa e máx 100 - aceita zero
                    "largura" => $productVariant->getWidth(), // acima de 70 taxa e máx 100 - aceita zero
                    "comprimento" => $productVariant->getDepth(), // acima de 70 taxa e máx 100 - aceita zero
                    "valor" => 0, // Aceita zero
                    "quantidade" => 1, // Aceita zero e o Máximo é com base na soma dos atributos dos produtos
                ]
            ],
            "servicos" => ["string"],
            "ordernar" => "string"
        ];


        $entrada = [
            'origem' => $cepOrigem,
            'destino' => $cepDestino,
            "peso" => $productVariant->getWeight(),
            "altura" => $productVariant->getHeight(),
            "largura" => $productVariant->getWidth(),
            "comprimento" => $productVariant->getDepth(),
        ];

        $headers = [
            "accept" => "application/json",
            "token" => $token,
            "Content-Type" => "application/json",
        ];

        try {
            $client = new \GuzzleHttp\Client();

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $requestData,
            ]);

            $statusCode = $response->getStatusCode();
            $bodyContents = $response->getBody()->getContents();
            $data = json_decode($bodyContents, true);

            if(isset($data['error'])){
                $restricao = $data['error']['mensagem'];
                return $this->render('Context/Shop/Checkout/kanguShipping.html.twig',['restricao' => $restricao, 'entrada' => $entrada]);
            }
        
            if ($statusCode >= 200 && $statusCode < 300) {
                $data = json_decode($bodyContents, true);
                return $this->render('Context/Shop/Checkout/kanguShipping.html.twig', ['dados' => $data, 'entrada' => $entrada]);
            }

        } catch (RequestException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $errorMessage = $e->getMessage();
            $restricao = 'Status: ' . $statusCode . ' - ' .$errorMessage;
            return $this->render('Context/Shop/Checkout/kanguShipping.html.twig',['restricao' => $restricao, 'entrada' => $entrada]);
        }
    }
}
