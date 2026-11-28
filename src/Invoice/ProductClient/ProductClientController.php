<?php

declare(strict_types=1);

namespace App\Invoice\ProductClient;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\ProductClient\ProductClientForm;
use App\Invoice\ProductClient\ProductClientService;
use App\Invoice\ProductClient\ProductClientRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Client\ClientOptionLabels;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Product\ProductRepository;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use Exception;
use DateTimeImmutable;

final class ProductClientController extends BaseController
{
    protected string $controllerName = 'invoice/productclient';

    public function __construct(
        private ProductClientService $productclientService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct(
            $webService,
            $userService,
            $translator,
            $webViewRenderer,
            $session,
            $sR,
            $flash
        );
        $this->productclientService = $productclientService;
    }

    /**
     * Handle batch association of multiple products from family commalist
     * Either associate a current client with the product e.g. house or
     * create a new client and associate it with the product.
     *
     * When the client signsup, use settings 'Invoice User Account' to associate
     * the above (new) client with the signedup observer account in the
     * invoice/userinv/index table.
     *
     * @param Request $request
     * @param ClientRepository $clientRepository
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function associateMultiple(
        Request $request,
        ClientRepository $clientRepository,
        ProductRepository $productRepository
    ): Response {
        $queryParams = $request->getQueryParams();
        /** @var array<int> $productIds */
        $productIds = [];
        if (isset($queryParams['product_ids']) && is_string($queryParams['product_ids'])) {
            $productIds = array_filter(
                array_map('intval', explode(',', $queryParams['product_ids'])),
                fn (int $id): bool => $id > 0
            );
        }
        if (empty($productIds)) {
            $this->flash->add('danger', $this->translator->translate('no.products.specified.for.association'), true);
            return $this->webService->getRedirectResponse('family/index');
        }
        return $this->processAssociation($request, $productIds, (int)($queryParams['index'] ?? 0), $clientRepository, $productRepository);
    }

    /**
     * @param array<int> $productIds
     */
    private function processAssociation(
        Request $request,
        array $productIds,
        int $currentIndex,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
    ): Response {
        if ($currentIndex >= count($productIds)) {
            $this->flash->add('success', $this->translator->translate('product.client.associations.completed'), true);
            return $this->webService->getRedirectResponse('productclient/index');
        }
        $currentProductId = $productIds[$currentIndex];
        $product = $productRepository->repoProductQuery($currentProductId);
        if (!$product) {
            $this->flash->add('danger', $this->translator->translate('product.not.found') . ': ' . $currentProductId, true);
            return $this->webService->getRedirectResponse('family/index');
        }
        $body = $request->getParsedBody();
        $redirect = null;
        if ($request->getMethod() === Method::POST && is_array($body)) {
            /** @var array<string, mixed> $body */
            $redirect = $this->handleExistingClientAssociation($body, $productIds, $currentIndex, $currentProductId, $clientRepository);
        }
        $parameters = [
            'title' => $this->translator->translate('associate.product.with.client'),
            'actionName' => 'productclient/associate-multiple',
            'actionArguments' => ['product_ids' => implode(',', $productIds), 'index' => $currentIndex],
            'errors' => [],
            'form' => ProductClientForm::show(new ProductClient(), $currentProductId, null),
            'clients' => $this->buildClientOptionsArray($clientRepository->findAllPreloaded()),
            'product' => $product,
            'productRepository' => $productRepository,
            'productId' => $currentProductId,
            'currentIndex' => $currentIndex + 1,
            'totalProducts' => count($productIds),
            'remainingProducts' => count($productIds) - $currentIndex,
        ];
        return $redirect ?? $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<int> $productIds
     */
    private function handleExistingClientAssociation(
        array $body,
        array $productIds,
        int $currentIndex,
        int $currentProductId,
        ClientRepository $clientRepository,
    ): ?Response {
        $clientId = (int)($body['client_id'] ?? 0);
        if ($clientRepository->repoClientCount($clientId) > 0) {
            $this->createProductClientAssociation($currentProductId, $clientId);
            return $this->redirectToNextProduct($productIds, $currentIndex + 1);
        }
        return null;
    }

    /**
     * Create ProductClient association
     */
    private function createProductClientAssociation(int $productId, int $clientId): void
    {
        try {
            $productClient = new ProductClient();
            $associationData = [
                'product_id' => $productId,
                'client_id' => $clientId,
                'created_at' =>  new DateTimeImmutable()->format('Y-m-d'),
                'updated_at' =>  new DateTimeImmutable()->format('Y-m-d'),
            ];

            $this->productclientService->save($productClient, $associationData);

        } catch (Exception $e) {
            $this->flash->add(
                'danger',
                $this->translator->translate('error.creating.association') .
                    ': ' . $e->getMessage(),
                true
            );
        }
    }

    /**
     * Redirect to next product or complete the batch process
     * @param array<int> $productIds
     */
    private function redirectToNextProduct(array $productIds, int $nextIndex): Response
    {
        if ($nextIndex >= count($productIds)) {
            // All products processed
            $this->flash->add(
                'success',
                $this->translator->translate('product.client.associations.completed'),
                true
            );
            return $this->webService->getRedirectResponse('product/index');
        }

        // Redirect to next product
        return $this->webService->getRedirectResponse('productclient/associate-multiple', [
            'product_ids' => implode(',', $productIds),
            'index' => $nextIndex
        ]);
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
        #[RouteArgument('productId')] string $productId,
        #[RouteArgument('clientId')] string $clientId,
    ): Response {
        $productclient = new ProductClient();
        $form = ProductClientForm::show($productclient, (int) $productId, (int) $clientId);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'productclient/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'clients' => $clientRepository->findAllPreloaded(),
            'products' => $productRepository->findAllPreloaded(),
            'productRepository' => $productRepository,
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate(
                    $form,
                    $request
                )) {
                    $this->productclientService
                         ->save($productclient, $body);
                    return $this->webService
                                ->getRedirectResponse('productclient/index');
                }
                $parameters['errors'] =
                    $form->getValidationResult()
                         ->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * @param ProductClientRepository $productClientRepository
     * @param int $id
     * @return Response
     */
    public function delete(
        ProductClientRepository $productClientRepository,
        #[RouteArgument('id')] int $id
    ): Response {
        try {
            $productclient = $this->productclient($productClientRepository, $id);
            if ($productclient) {
                $this->productclientService
                     ->delete($productclient);
                $this->flashMessage(
                    'info',
                    $this->translator
                         ->translate('record.successfully.deleted')
                );
                return $this->webService
                            ->getRedirectResponse('productclient/index');
            }
            return $this->webService->getRedirectResponse('productclient/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('productclient/index');
        }
    }

    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        ProductClientRepository $productclientRepository,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
        #[RouteArgument('id')] int $id
    ): Response {
        $productclient = $this->productclient($productclientRepository, $id);
        if ($productclient) {
            $form = ProductClientForm::show(
                $productclient,
                $productclient->getProductId(),
                $productclient->getClientId()
            );
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'productclient/edit',
                'actionArguments' => ['id' => $id],
                'errors' => [],
                'form' => $form,
                'clients' => $clientRepository->findAllPreloaded(),
                'products' => $productRepository->findAllPreloaded(),
                'productRepository' => $productRepository,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator
                             ->populateFromPostAndValidate($form, $request)) {
                        $this->productclientService
                             ->save($productclient, $body);
                        return $this->webService
                                    ->getRedirectResponse('productclient/index');
                    }
                    $parameters['errors'] =
                        $form->getValidationResult()
                             ->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
            }
            return $this->webViewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('productclient/index');
    }

    /**
     * @param ProductClientRepository $productclientRepository
     * @param int $id
     * @return ProductClient|null
     */
    private function productclient(
        ProductClientRepository $productclientRepository,
        int $id
    ): ProductClient|null {
        if ($id) {
            return $productclientRepository->repoProductClientQuery($id);
        }
        return null;
    }

    /**
     * @param ProductClientRepository $productclientRepository
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(
        ProductClientRepository $productclientRepository,
        #[RouteArgument('id')] int $id
    ): \Psr\Http\Message\ResponseInterface {
        $productclient = $this->productclient($productclientRepository, $id);
        if ($productclient) {
            $product = $productclient->getProduct();
            $client = $productclient->getClient();

            $form = ProductClientForm::show(
                $productclient,
                $productclient->getProductId(),
                $productclient->getClientId()
            );
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'productclient/view',
                'actionArguments' => ['id' => $id],
                'form' => $form,
                'productClient' => $productclient,
                'product' => $product,
                'client' => $client,
                'alert' => '',
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('productclient/index');
    }

    /**
     * Build client options array for dropdown
     * @param \Yiisoft\Data\Cycle\Reader\EntityReader $clients
     * @return array<string, string>
     */
    private function buildClientOptionsArray(
        \Yiisoft\Data\Cycle\Reader\EntityReader $clients
    ): array {
        /** @var list<Client> $all */
        $all = [];
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $all[] = $client;
        }
        // clients that share a name are told apart (email, else postcode, else number), see ClientOptionLabels
        $labels = ClientOptionLabels::for($all, true);
        $options = [];
        foreach ($all as $client) {
            $status = $client->getClientActive() ?
                $this->translator->translate('active') :
                $this->translator->translate('inactive');
            $options[(string) $client->reqId()] = $labels[$client->reqId()] . ' ' . $status;
        }
        // alphabetical, so a client can be found in a long list; the keys (client ids) are kept
        uasort($options, static fn (string $a, string $b): int => strnatcasecmp($a, $b));
        return $options;
    }
}
